#!/bin/bash
FILE=$1
curl https://api.openai.com/v1/audio/transcriptions \
  -H "Authorization: Bearer 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" \
  -H "Content-Type: multipart/form-data" \
  -F response_format="text" \
  -F model="whisper-1" \
  -F file="@$FILE"