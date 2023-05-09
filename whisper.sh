#!/bin/bash
FILE=$1
curl https://api.openai.com/v1/audio/transcriptions \
  -H "Authorization: Bearer $OPENAI_API_KEY" \
  -H "Content-Type: multipart/form-data" \
  -F response_format="verbose_json" \
  -F model="whisper-1" \
  -F file="@$FILE"