@echo off
echo ============================================
echo Starting Chroma Vector Database Server
echo ============================================
echo.

REM Update this path to your preferred location
REM You can change this to Google Drive path when it's synced
set CHROMA_PATH=C:\ChromaDB

echo Database Path: %CHROMA_PATH%
echo Server: http://localhost:8000
echo.

REM Check if chromadb is installed
py -c "import chromadb" 2>nul
if errorlevel 1 (
    echo ChromaDB not found. Installing dependencies...
    py -m pip install -r requirements.txt
    echo.
)

echo Starting Chroma server...
echo Press Ctrl+C to stop the server
echo.

chroma run --path "%CHROMA_PATH%" --port 8000 --host 127.0.0.1
