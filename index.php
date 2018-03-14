<?php 
  session_start();
  require('dbconnect.php');

  echo '<br>';
  echo '<br>';
  echo '<br>';
  echo '<br>';
  echo '<br>';

  // ログインちぇっく
  // 一時間ログインしていない場合、再度ログイン
  if(isset($_SESSION['id']) && $_SESSION['time'] + 3600 > time()){
    // ログインしている
    // ログイン時間の更新
    $_SESSION['time'] = time();

      // ログインユーザーの情報を取得
    $login_sql = 'SELECT * FROM `members` WHERE `member_id` = ? ';
    $login_data = array($_SESSION['id']);
    $login_stmt = $dbh->prepare($login_sql);
    $login_stmt->execute($login_data);

    $login_user = $login_stmt->fetch(PDO::FETCH_ASSOC);
  }else{
    // ログインしていない,または時間切れ
    // ログイン画面へ強制遷移する
    header('Location: login.php');
  }

  // 呟くボタンが押された時
  if (!empty($_POST)) {
    // 入力チェック
    if ($_POST['tweet'] == '') {
      $error['tweet'] = 'blank';
    }

    if (!isset($error)) {
      $sql = 'INSERT INTO `tweets` SET `tweet` = ?, `member_id` = ?, `reply_tweet_id` = -1, `created` = NOW(), `modified` = NOW()';
      $data = array($_POST['tweet'], $_SESSION['id']);
      $stmt = $dbh->prepare($sql);
      $stmt->execute($data);
    }
  }

  // ページング機能
  // 空の変数を用意
  $page = '';

  // パラメータが存在していた場合ページ番号を代入
  if (isset($_GET['page'])) {
    $page = $_GET['page'];
  }else{
    $page = 1;
  }

  // 1以外のイレギュラーな数字が入ってきた時、ページ番号を強制的に１とする
  // max　カンマ区切りで羅列された数字の中から最大の数字を取得する
  $page = max($page, 1);

  // 1ページ分の表示件数を指定
  $page_number = 5;

  // データの件数から最大ページを計算する
  $page_sql = 'SELECT COUNT(*) AS `page_count` FROM `tweets` WHERE `delete_flag` = 0';
  $page_stmt = $dbh->prepare($page_sql);
  $page_stmt->execute();

  $page_count = $page_stmt->fetch(PDO::FETCH_ASSOC);

  $all_page_number = ceil($page_count['page_count'] / $page_number);
  // パラメータのページ番号が最大ページを超えていれば、強制的に最後のページとする
  $page = min($page, $all_page_number);

  // 表示するデータの取得開始場所
  $start = ($page - 1) * $page_number;






  // 一覧用の投稿全件取得
  // テーブル結合
  //  INNER JOIN と OUTER JOIN(left join と right join)
  // INNER JOIN = 両方のテーブルに存在するデータのみ取得
  // OUTER JOIN(left join と right join) = 複数のテーブルがあり、それらを結合するときに優先テーブルをひとつきめ、そこにある情報はすべて表示しながら、他のテーブルの情報についになるデータがあれば表示する。
  // 優先テーブルに指定されるとそのテーブルの情報はすべて表示される。
  $tweet_sql = "SELECT `tweets`.*, `members`.`nick_name`, `members`.`picture_path` FROM `tweets` LEFT JOIN `members` ON `tweets`.`member_id` = `members`.`member_id` WHERE `delete_flag` = 0 ORDER BY `tweets`.`modified` DESC LIMIT ".$start.",".$page_number;
  $tweet_stmt = $dbh->prepare($tweet_sql);
  $tweet_stmt->execute();

  $tweet_list = array();
  while(true){

    $tweet = $tweet_stmt->fetch(PDO::FETCH_ASSOC);
    if($tweet == false){
      break;
    }
    $tweet_list[] = $tweet;
  }
  // var_dump($tweet_list);


  // $modified_sql = 'SELECT `tweets`.`modified` FROM `tweets` LEFT JOIN `members` ON `tweets`.`member_id` = `members`.`member_id` ORDER BY `tweets`.`modified` DESC';
  // $modified_stmt = $dbh->prepare($modified_sql);
  // $modified_stmt->execute();
  // $modified = $modified_stmt->fetch(PDO::FETCH_ASSOC);

 ?>

<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>SeedSNS</title>

    <!-- Bootstrap -->
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet">
    <link href="assets/css/form.css" rel="stylesheet">
    <link href="assets/css/timeline.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

  </head>
  <body>
  <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
          <!-- Brand and toggle get grouped for better mobile display -->
          <div class="navbar-header page-scroll">
              <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                  <span class="sr-only">Toggle navigation</span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
                  <span class="icon-bar"></span>
              </button>
              <a class="navbar-brand" href="join/index.php"><span class="strong-title"><i class="fa fa-twitter-square"></i> Seed SNS</span></a>
          </div>
          <!-- Collect the nav links, forms, and other content for toggling -->
          <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
              <ul class="nav navbar-nav navbar-right">
                <li><a href="logout.php">ログアウト</a></li>
              </ul>
          </div>
          <!-- /.navbar-collapse -->
      </div>
      <!-- /.container-fluid -->
  </nav>

  <div class="container">
    <div class="row">
      <div class="col-md-4 content-margin-top">
        <legend>ようこそ<?php echo $login_user['nick_name'] ?>さん！</legend>
        <form method="post" action="" class="form-horizontal" role="form">
            <!-- つぶやき -->
            <div class="form-group">
              <label class="col-sm-4 control-label">つぶやき</label>
              <div class="col-sm-8">
                <textarea name="tweet" cols="50" rows="5" class="form-control" placeholder="例：Hello World!"></textarea>
                <?php if(isset($error['tweet']) && $error['tweet'] == 'blank'): ?>
                  <p class="error">つぶやき内容を入力してください。</p>
                <?php endif ?>
              </div>
            </div>
          <ul class="paging">
            <input type="submit" class="btn btn-info" value="つぶやく">
                &nbsp;&nbsp;&nbsp;&nbsp;
                <?php if ($page == 1): ?>
                  <li>前</li>
                <?php else: ?>
                  <li><a href="index.php?page=<?php echo $page -1; ?>" class="btn btn-default">前</a></li>
                <?php endif ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <?php if ($page == $all_page_number): ?>
                  <li>次</li>
                <?php else: ?>
                  <li><a href="index.php?page=<?php echo $page +1; ?>" class="btn btn-default">次</a></li>
                <?php endif ?>
                <li><?php echo $page; ?>/<?php echo $all_page_number; ?></li>
          </ul>
        </form>
      </div>

      <div class="col-md-8 content-margin-top">
        <?php foreach($tweet_list as $tweet): ?>
        <div class="msg">
          <img src="picture_path/<?php echo $tweet['picture_path'] ?>" width="48" height="48">
          <p>
            <?php echo $tweet['tweet']; ?><span class="name"> (<?php echo $tweet['nick_name']; ?>) </span>
            <?php if ($tweet['member_id'] != $login_user['member_id']): ?>
            [<a href="#">Re</a>]
            <?php endif ?>
          </p>
          <p class="day">
            <a href="view.php?tweet_id=<?php echo $tweet['tweet_id'] ?>">
              <?php echo date('y-m-d h:i', strtotime($tweet['modified'])); ?>
            </a>
            <?php if ($tweet['member_id'] == $login_user['member_id']): ?>
            [<a href="edit.php?id=<?php echo $tweet['tweet_id'] ?>" style="color: #00994C;">編集</a>]
            [<a href="delete.php?action=delete&tweet_id=<?php echo $tweet['tweet_id']; ?>" style="color: #F33;">削除</a>]
            <?php endif ?>
          </p>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="assets/js/jquery-3.1.1.js"></script>
    <script src="assets/js/jquery-migrate-1.4.1.js"></script>
    <script src="assets/js/bootstrap.js"></script>
  </body>
</html>
