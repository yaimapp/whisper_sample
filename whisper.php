<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Whisperによる文字起こし</title>
  </head>
  <body>
    <h1>文字起こしアプリ</h1>
    <h2>音声ファイルをアップロードしてください</h2>
    <p>動作確認済みファイルタイプは mp3, m4a です</p>
    <p>ファイルアップロード後、しばらくするとテキストファイルがダウンロードできます</p>
    <form action="whisper.php" method="post" enctype="multipart/form-data">
      <input type="file" name="audio_file" accept="audio/*"><br>
      <input type="submit" value="アップロード">
    </form>
  </body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_FILES["audio_file"]) && $_FILES["audio_file"]["error"] == 0) {
    // 作業ディレクトリ
    $tmp_dir = "tmp/";
    $allow_extensions = ["mp3", "mp4", "mpeg", "mpga", "m4a", "wav", "webm"];
    // 拡張子を取得してファイルの種類を特定
    $file_name = basename($_FILES["audio_file"]["name"]);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $file_type = "audio/";
    if (in_array($file_extension, $allow_extensions)) {
      $file_type .= $file_extension;
    } else {
      die("対応していないファイルタイプです。");
    }

    $new_file_name = $tmp_dir . uniqid() . $file_name;
    if (move_uploaded_file($_FILES["audio_file"]["tmp_name"], $new_file_name)) {
      echo "ファイルがアップロードされました。<br />\n";
    } else {
      die("ファイルをアップロードできませんでした。");
    }
    // APIの情報
    $api_key = getenv('OPENAI_API_KEY');
    $model_id = 'whisper-1';
    $base_url = "https://api.openai.com";
    // // 音声ファイルのパス
    $audio_file_path = './' . $new_file_name;
    // リクエストの準備
    $headers = [
      "Authorization: Bearer {$api_key}",
      "Content-Type: multipart/form-data"
    ];
    $data = [
      'response_format' => 'text',
      'model' => $model_id,
      'file' => $cfile = curl_file_create($audio_file_path, $file_type)
    ];
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$base_url}/v1/audio/transcriptions",
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => $headers,
    ]);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    // リクエスト送信
    echo "リクエスト送信中...<br />\n";
    $response = curl_exec($curl);
    if ($response === false) {
      die(curl_error($curl));
    }
    curl_close($curl);
    // 結果をファイルに保存する
    $filename = './' . $tmp_dir . uniqid() . 'transciption.txt';
    $file = fopen($filename, "w") or die("ファイルが開けません!");

    fwrite($file, $response);

    fclose($file);

    // ファイルが正常に保存されたかどうかを確認する
    if(file_exists($filename)){
        echo "テキストファイルに保存しました。<br /><br />\n";
    } else {
        die( "エラー: ファイルを保存できませんでした。");
    }
    echo "<a href='$filename' download>ファイルをダウンロードする</a>\n";
    echo "<br />\n";
    // // アップロードしたファイルは削除する
    if (!unlink($audio_file_path)) {
      echo "音声ファイルを削除できませんでした。<br />\n";
    }
  } else {
    echo "ファイルがアップロードされていません。";
  }
}
?>