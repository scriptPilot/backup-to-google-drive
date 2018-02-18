<?php

  /**
   * Purpose: Remove old revisions of a set of files per user
   */

  class cleanup {

    private $startTime;
    private $maxRuntime = 300;
    private $logs = [];
    private $errors = 0;
    private $actions = 0;
    private $gAuth;
    private $gDrive;
    private $userId;
    private $cache = [];
    private $batchSize = 10;

    public function start() {

      header('Content-type: text/html;charset=utf-8');
      date_default_timezone_set('Europe/Berlin');

      $this->startTime = time();
      $this->lockScript();
      $this->log('Started cleanup script');

      require('config.php');
      require('lib/Google/Auth.php');
      require('lib/Google/Drive.php');

      $this->gAuth = new \Google\Auth(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI);
      $this->gAuth->addScope('https://www.googleapis.com/auth/drive');
      $this->gAuth->addScope('profile');
      $this->gDrive = new \Google\Drive($this->gAuth->getToken());

      $this->doCleanup();

    }

    private function stop($unlock = true) {
      $this->writeCache();
      $this->printLogs();
      if ($unlock !== false) $this->unlockScript();
      exit;
    }

    private function getLockFileName() {
      $name = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.lock';
      return $name;
    }

    private function lockScript() {
      $lockFile = $this->getLockFileName();
      if (file_exists($lockFile)) {
        $start = intval(file_get_contents($lockFile));
        $duration = $this->startTime - $start;
        if ($duration > $this->maxRuntime + 5 * 60) {
          $this->unlockScript();
          $this->log('Unlocked script automatically');
        } else {
          $this->log('Script locked for ' . $duration . ' seconds now');
          $this->stop(false);
        }
      } else {
        file_put_contents($lockFile, $this->startTime);
      }
    }

    private function unlockScript() {
      $name = $this->getLockFileName();
      if (file_exists($name)) unlink($name);
    }

    private function getCredentials() {
      $credentials = [];
      $dir = '.credentials';
      if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
          if (substr($file, -11) === '.google.php') {
            $content = file_get_contents($dir . '/' . $file);
            preg_match('/\/\/(.+)\\n/', $content, $search);
            $credentials[] = json_decode(trim($search[1]), true);
          }
        }
      }
      return $credentials;
    }

    private function doCleanup() {
      // Loop users
      $credentials = $this->getCredentials();
      foreach ($credentials as $credential) {

        $this->checkRuntime();

        // Update credentials
        $this->gAuth->setCredentials($credential);
        $this->gDrive->setToken($this->gAuth->getToken());
        $userInfo = $this->gAuth->getUserInfo();
        $this->userId = $userInfo['id'];

        // Log current user name
        $this->log('<b>Started cleanup for ' . $userInfo['displayName'] . '</b>');

        // Do cleanup per user
        $this->cleanupPerUser();

      }
      $this->stop();
    }

    private function cleanupPerUser() {

      $this->readCache();

      $this->cleanupBatch();

    }

    private function cleanupBatch() {

      $files = $this->getFiles();

      if (is_array($files)) {

        if (isset($files['nextPageToken'])) $this->cache['nextPageToken'] = $files['nextPageToken'];
          else unset($this->cache['nextPageToken']);

        // Loop files
        foreach ($files['files'] as $file) {

          // Loop revisions
          if (is_array($file['revisions'])) {
            if (count($file['revisions']) > 1) {
              for ($n=0; $n < count($file['revisions']) - 1; $n++) {
                $removal = $this->deleteRevision($file['id'], $file['revisions'][$n]['id']);
                if ($removal) $this->log('Deleted revision #' . $n . ' of file "' . $file['name'] . '"');
                  else $this->log('Failed to delete revision #' . $n . ' of file "' . $file['name'] . '"');
              }
            } else {
              $this->log('Revisions already cleaned up for file "' . $file['name'] . '"');
            }
          } else {
            $this->log('No revisions found for file "' . $file['name'] . '"');
          }

        }

        $this->checkRuntime();

        $this->cleanupBatch();

      } else {

        $this->log('No more files found');

      }

    }

    private function deleteRevision($fileId, $revisionId) {
      // Create REST URI
      $restUri = 'https://www.googleapis.com/drive/v3/files/' . $fileId . '/revisions/' . $revisionId
               . '?access_token=' . $this->gAuth->getToken();
      // Perform cURL request
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => $restUri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => ['Content-Length: 0']
      ]);
      // Handle request response
      $response = curl_exec($curl);
      curl_close($curl);
      if ($response === false) return false;
      return true;
    }

    private function getFiles() {
      $baseUri = 'https://www.googleapis.com/drive/v3/files';
      $params = [
        'access_token' => $this->gAuth->getToken(),
        'q' => 'trashed=false and mimeType != "application/vnd.google-apps.folder"',
        'fields' => 'files(id,name),nextPageToken',
        'pageSize' => $this->batchSize
      ];
      if (isset($this->cache['nextPageToken'])) $params['pageToken'] = $this->cache['nextPageToken'];
      $restUri = $baseUri . '?' . http_build_query($params);
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => $restUri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true
      ]);
      $response = curl_exec($curl);
      if ($response === false) return false;
      $array = json_decode($response, true);
      curl_close($curl);
      foreach ($array['files'] as $key => $file) {
        $array['files'][$key]['revisions'] = $this->getRevisions($file['id']);
      }
      return $array;
    }

    private function getRevisions($fileId) {
      $restUri = 'https://www.googleapis.com/drive/v3/files/' . $fileId . '/revisions'
               . '?access_token=' . $this->gAuth->getToken();
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => $restUri,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true
      ]);
      $response = curl_exec($curl);
      if ($response === false) return false;
      $revisions = json_decode($response, true)['revisions'];
      curl_close($curl);
      return $revisions;
    }

    private function getCacheFileName() {
      $name = '.cache/' . $this->userId . '.' . basename($_SERVER['SCRIPT_FILENAME'], '.php');
      return $name;
    }

    private function readCache() {
      if ($this->userId !== null) {
        $name = $this->getCacheFileName();
        $this->cache = file_exists($name) ? unserialize(file_get_contents($name)) : [];
      }
    }

    private function writeCache() {
      if (!is_dir('.cache')) mkdir('.cache');
      $name = $this->getCacheFileName();
      if ($this->userId !== null && count($this->cache) > 0) {
        file_put_contents($name, serialize($this->cache));
      } else if (file_exists($name)) {
        unlink($name);
      }
    }

    private function checkRuntime() {
      $now = time();
      if ($now - $this->startTime >= $this->maxRuntime) {
        $this->log('Stopped script automatically, the maximum runtime of ' . $this->maxRuntime . ' seconds is reached');
        $this->stop();
      }
    }

    private function log($text) {
      $timeStr = '<span style="color: grey">' . date('H:i:s') . ' - </span>';
      $text = $timeStr . $text;
      if (strpos($text, 'Failed') > -1) {
        $this->logs[] = '<span style="color: red">' . $text . '</span>';
        $this->errors += 1;
      } else if (strpos($text, 'Created') > -1) {
        $this->logs[] = '<span style="color: green">' . $text . '</span>';
        $this->actions += 1;
      } else if (strpos($text, 'Updated') > -1 || strpos($text, 'Renamed') > -1) {
        $this->logs[] = '<span style="color: blue">' . $text . '</span>';
        $this->actions += 1;
      } else if (strpos($text, 'Trashed') > -1 || strpos($text, 'Deleted') > -1) {
        $this->logs[] = '<span style="color: orange">' . $text . '</span>';
        $this->actions += 1;
      } else {
        $this->logs[] = $text;
      }
    }

    private function printLogs() {
      echo '<html><head></head><body>';
      echo implode('<br />', $this->logs);
      if (count($this->logs) > 0) echo '<br />';
      if ($this->errors > 0) {
        echo '<span style="color: red">Cronjob finished with ' . $this->errors . ' error' . ($this->errors !== 1 ? 's' : '') . '</span>';
      } else if ($this->actions > 0) {
        echo '<span style="color: green">Cronjob finished successfull with ' . $this->actions . ' action' . ($this->actions !== 1 ? 's' : '') . '.</span>';
      } else {
        echo '<span style="color: green">Cronjob finished successfull without any action.</span>';
      }
      echo '</body></html>';
    }

    private function debug($input) {
      echo '<pre>';
      print_r($input);
      echo '</pre>';
    }

  }

  $clean = new cleanup;
  $clean->start();

?>
