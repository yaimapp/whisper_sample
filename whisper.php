<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
$html = <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Whisperによる文字起こし</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </head>
    <body>
        <h1>文字起こしアプリ</h1>
        <h2>音声ファイルをアップロードしてください</h2>
        <p>動作確認済みファイルタイプは mp3, m4a です</p>
        <p>ファイルサイズは25MB以内でお願いします</p>
        <p>ファイルアップロード後、しばらくするとテキストファイルがダウンロードできます</p>
        <form enctype="multipart/form-data" id="my-form">
        <input type="file" name="audio_file" accept="audio/*"><br>
        <input type="button" value="アップロード" id="upload-button">
        </form>
        <div id="loading-icon" style="display:none;">
        処理中...
        <i class="fa fa-spinner fa-spin" style="font-size:24px"></i>
        </div>
        <div id="result" style="margin-top:24px;">
        </div>
        <script>
        document.getElementById("upload-button").addEventListener("click", function() {
            // ローディングアイコンを表示する
            document.getElementById("loading-icon").style.display = "block";
            // フォームを送信する
            var form = document.getElementById("my-form");
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "whisper.php");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
					console.info(xhr.response);
					let res = JSON.parse(xhr.response);
                    let result = document.getElementById("result");
                    if (xhr.status === 200) {
						let anchor = document.createElement("a");
						anchor.download = "download.txt";
						anchor.href = res.filename;
						result.appendChild(anchor);
						let textnode = document.createTextNode("ファイルをダウンロードする");
						anchor.appendChild(textnode);
                    } else if (xhr.status === 400) {
                        let textnode = document.createTextNode(res.message);
                        result.appendChild(textnode);
                    }
                    document.getElementById("loading-icon").style.display = "none";
                }
            };
            xhr.send(new FormData(form));
        });
        </script>
    </body>
</html>
EOF;
echo $html;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES["audio_file"]) && $_FILES["audio_file"]["error"] == 0) {
        // 作業ディレクトリ
        $tmp_dir = "tmp/";
        // 対応している音声ファイル
        $allow_extensions = ["mp3", "m4a", "wav", "mpga"];
        // 拡張子を取得してファイルの種類を特定
        $file_name = basename($_FILES["audio_file"]["name"]);
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_type = "audio/";
        if (in_array($file_extension, $allow_extensions)) {
            $file_type .= $file_extension;
        } else {
            response_error("エラー: 対応していないファイルタイプです。");
        }
        // ファイルをアップロードして作業ディレクトリに保存
        $new_file_name = $tmp_dir . uniqid() . $file_name;
        if (!move_uploaded_file($_FILES["audio_file"]["tmp_name"], $new_file_name)) {
            response_error("エラー: ファイルをアップロードできませんでした。");
        }
        // APIの情報
        $api_key = getenv('OPENAI_API_KEY'); // 環境変数から読み込む
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
            'file' => curl_file_create($audio_file_path, $file_type)
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
        $response = curl_exec($curl);
        if ($response === false) {
            response_error(curl_error($curl));
        }
        curl_close($curl);
        // 結果をファイルに保存する
        $filename = './' . $tmp_dir . uniqid() . 'transciption.txt';
        $file = fopen($filename, "w") or response_error("エラー:ファイルが開けません!");

        fwrite($file, $response);
        fclose($file);

        // ファイルが正常に保存されたかどうかを確認する
        if(!file_exists($filename)){
            response_error("エラー：ファイルを保存できませんでした");
        }
        // アップロードしたファイルは削除する
        if (!unlink($audio_file_path)) {
            // echo "音声ファイルを削除できませんでした。<br />\n";
        }
        header("HTTP/1.1 200 OK");
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(['filename' => $filename], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        response_error("エラー: ファイルがアップロードされていません。");
    }
}

function response_error($message = 'エラーが発生しました') {
    // ステータスコードの設定
    header("HTTP/1.1 400 Bad Request");
    // コンテンツタイプ
    header("Content-Type: application/json; charset=utf-8");
    // レスポンスボディにエラーの詳細を記載
    $result = ['code' => 400, 'message' => $message];
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}
