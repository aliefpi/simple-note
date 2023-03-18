<?php
if (!class_exists('Simple_Note')) {
  /**
   * @package Simple Note
   * @version v1.0
   * @author @aliefpi
   */
  class Simple_Note
  {
    /**
     * Set base url.
     * 
     */
    private $baseUrl = 'http://localhost:7575';

    /**
     * User cookie name.
     * 
     */
    private $cookieName = 'SN_USER';

    /**
     * Simple note config.
     * 
     */
    private $config = [
      /**
       * Note types.
       * 
       */
      'note_types' => [
        'note' => 'یادداشت',
        'idea' => 'ایده',
        'task' => 'تسک',
        'reminder' => 'یادآوری'
      ],

      /**
       * Note Status.
       * 
       */
      'note_status' => [
        'waiting' => 'در انتظار',
        'doing' => 'درحال انجام',
        'reviewing' => 'درحال بررسی',
        'done' => 'انجام شده',
        'canceled' => 'لغو شده'
      ],

      /**
       * Database connection config.
       * 
       */
      'database' => [
        'host' => 'localhost',
        'name' => 'simplenote',
        'user' => 'root',
        'pass' => ''
      ],
    ];

    private static $instance = null;

    protected $database = null;

    public static function instance()
    {
      if (is_null(self::$instance)) {
        self::$instance = new self();
      }

      return self::$instance;
    }

    /**
     * Database connection.
     * 
     */
    public function db()
    {
      try {
        $dbConn = new \PDO(
          sprintf(
            'mysql:host=%s;dbname=%s',
            $this->config['database']['host'],
            $this->config['database']['name']
          ),
          $this->config['database']['user'],
          $this->config['database']['pass']
        );

        $dbConn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $dbConn->exec('SET NAMES utf8');
        $this->database = $dbConn;
      } catch (\PDOException $dbErr) {
        die($dbErr->getMessage());
      }
    }

    /**
     * Login user into account.
     * 
     */
    public function loginUser(string $username, string $password)
    {
      if (isset($_COOKIE[$this->cookieName])) {
        return false;
      }

      $password = sha1($password);

      $getUser = $this->database->prepare("SELECT * FROM `users` WHERE `username` = '$username' AND `password` = '$password'");
      $getUser->execute();

      if ($getUser->rowCount() === 1) {
        $user = $getUser->fetch(PDO::FETCH_ASSOC);
        setcookie($this->cookieName, (int) $user['id'], time() + (86400 * 30), '/');

        header('Location: ' . $this->baseUrl);
      }

      return false;
    }

    /**
     * Submit new note.
     * 
     */
    public function submitNote($request)
    {
      if (!isset($_COOKIE[$this->cookieName])) {
        return false;
      }

      $insertNote = $this->database->prepare("INSERT INTO `notes` (`note`, `author`, `type`, `status`, `created_at`, `updated_at`) VALUES (?,?,?,?,?,?)");
      $insertNote->bindValue(1, $request['note']);
      $insertNote->bindValue(2, $_COOKIE[$this->cookieName]);
      $insertNote->bindValue(3, $request['type']);
      $insertNote->bindValue(4, $request['status']);
      $insertNote->bindValue(5, date('Y-m-d H:i:s'));
      $insertNote->bindValue(6, date('Y-m-d H:i:s'));
      $insertNote->execute();

      return $insertNote;
    }

    /**
     * get notes.
     * 
     */
    public function getNotes(int $userId)
    {
      if (!isset($_COOKIE[$this->cookieName])) {
        return false;
      }

      $notes = $this->database->prepare("SELECT * FROM `notes` WHERE `author` = $userId ORDER BY `id` DESC");
      $notes->execute();

      return $notes->fetchAll();
    }

    /**
     * Render.
     * 
     */
    public function render()
    {
      /**
       * Define css styles.
       * 
       */

      $styles = [
        '*' => 'box-sizing:border-box;',
        'body' => 'direction:rtl;color:#ffffff;background-color:#192a56;font:13px tahoma;',
        '.container' => 'max-width:800px;min-width:400px;width:600px;background-color:#273c75;',
        'body,p,ul' => 'margin:0;',
        'ul' => 'padding:0;',
        'button' => 'padding:5px 20px;',
        'textarea,input,button,select' => 'font:13px tahoma;',
        'textarea,select,input' => 'border:1px solid #4a63a5;border-radius:5px;background-color:#192a56;color:#ffffff;padding:5px 10px;',
        'textarea' => 'resize:vertical;width:100%;padding:20px;',
        'body form' => 'display:flex;flex-direction:column;gap:30px;padding:30px;',
        'body form .form-submit' => 'display:flex;flex-direction:row;justify-content:space-between;',
        'body form .form-submit .submit' => 'background-color:#e74c3c;border-width:0;border-radius:10px;color:#ffffff;',
        'body form .form-submit .form-options' => 'display:flex;flex-direction:row;gap:15px;',
        '.notes' => 'display:flex;flex-direction:column;gap:15px;padding:30px;',
        '.notes .note' => 'background-color:#192a56;padding:15px;border-radius:5px;',
        '.notes .note ul' => 'display:flex;flex-direction:row;gap:15px;margin-top:10px;',
        '.notes .note ul li' => 'list-style:none;background-color:#273c75;padding:3px 10px;border-radius:3px;',
      ];

      $main = '';

      if (isset($_COOKIE[$this->cookieName])) {
        $main = vsprintf('<form id="form" class="form" action="%s" method="post" enctype="multipart/form-data">
          <textarea class="note" id="note" name="note" placeholder="شروع کن..."></textarea>
          <div class="form-submit">
            <button class="submit" name="submit-note" type="submit">بفرست!</button>
            <div class="form-options">
              <select name="type">%s</select>
              <select name="status">%s</select>
            </div>
          </div>
        </form>
        <div class="notes">%s</div>', [
          $this->baseUrl,
          implode("\n", array_map(function ($type, $typeKey) {
            return sprintf('<option value="%s">%s</option>', $typeKey, $type);
          }, $this->config['note_types'], array_keys($this->config['note_types']))),
          implode("\n", array_map(function ($status, $statusKey) {
            return sprintf('<option value="%s">%s</option>', $statusKey, $status);
          }, $this->config['note_status'], array_keys($this->config['note_status']))),
          implode("\n", array_map(function ($note) {
            return vsprintf('<div id="%s" class="note">
                <p>%s</p>
                <ul>
                  <li>%s</li>
                  <li>%s</li>
                </ul>
              </div>', [
              $note['id'],
              str_replace("\n", "<br />", $note['note']),
              $this->config['note_types'][$note['type']],
              $this->config['note_status'][$note['status']]
            ]);
          }, $this->getNotes($_COOKIE[$this->cookieName]))),
        ]);
      } else {
        $main = vsprintf('
        <form id="login-form" class="form" action="%s" method="post" enctype="multipart/form-data">
          <input type="text" name="username" value="" placeholder="نام کاربری" />
          <input type="password" name="password" value="" placeholder="گذرواژه" />
          <div class="form-submit">
            <button class="submit" name="submit-login" type="submit">ورود!</button>
          </div>
        </form>', [
          $this->baseUrl
        ]);
      }

      $render = vsprintf('<!DOCTYPE html>
      <html>
        <head>
          <title>%s</title>
          <meta charset="UTF-8">
          <style>%s</style>
        </head>
        <body>
          <div class="container">%s</div>
        </body>
      </html>', [
        'Simple Note.',
        implode("\n", array_map(function ($style, $styleKey) {
          return sprintf('%s{%s}', $styleKey, $style);
        }, $styles, array_keys($styles))),
        $main,
      ]);

      return $render;
    }

    public function __construct()
    {
      $this->db();

      if (isset($_COOKIE[$this->cookieName])) {
        if (isset($_POST['submit-note'])) {
          if (!empty($_POST['note']) && !empty($_POST['type']) && !empty($_POST['status'])) {
            $this->submitNote($_POST);
          }
        }
      } else {
        if (isset($_POST['submit-login'])) {
          if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $this->loginUser($_POST['username'], $_POST['password']);
          }
        }
      }

      echo $this->render();
    }
  }

  Simple_Note::instance();
}
