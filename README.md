# whisper_sample

## Whisperを使った文字起こしアプリ

### ファイルの設置
- whisper.php をPHPが実行できるディレクトリに設置します

### 作業ディレクトリの作成
- whisper.phpと同じところに tmp という名前でディレクトリを作成します
- phpプログラムからファイルの書き込みや削除ができるようにします

### 環境変数の設定
- OPENAIのAPI KEYを環境変数を設定する
- apacheの場合は /etc/apache2/envvars に書く
```
export OPENAI_API_KEY=your_api_key
```
- nginxの場合は /etc/nginx/sites-available/your_app に書く
```
...
  location ~ \.php$ {
    ...
    fastcgi_param  OPENAI_API_KEY your_api_key;
    ...
    ...
  }
...
```

### 使い方
- 音声ファイルを選択して、アップロードボタンを押します
- しばらくするとテキストファイルのダウンロードリンクが表示されます
- ファイルサイズは25MB以内
- 対応する音声ファイルの種類は mp3, m4a, wav, mpga

### シェルスクリプト
- 動作確認用シェルスクリプト
- 環境変数に OPENAI_API_KEY を設定
```
$ export OPENAI_API_KEY=your_api_key
```
- 同じディレクトリに voice.mp3 がある場合次のように実行
```
$ ./whisper.sh voice.mp3
```
- 変換したテキストが帰ってくる
