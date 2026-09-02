"""
Chroma Vector Database Server
Stores portfolio knowledge base in Google Drive synced folder
"""

import chromadb
from chromadb.config import Settings
import os
from pathlib import Path

# Configure Chroma to store data in Google Drive
# UPDATE THIS PATH to your Google Drive folder
GOOGLE_DRIVE_PATH = os.path.expanduser("~/Google Drive/ChromaDB")

# Create directory if it doesn't exist
Path(GOOGLE_DRIVE_PATH).mkdir(parents=True, exist_ok=True)

print(f"Starting Chroma server...")
print(f"Database location: {GOOGLE_DRIVE_PATH}")

# Initialize Chroma client with persistent storage in Google Drive
client = chromadb.PersistentClient(
    path=GOOGLE_DRIVE_PATH,
    settings=Settings(
        anonymized_telemetry=False,
        allow_reset=True
    )
)

# Create or get the portfolio collection
try:
    collection = client.get_or_create_collection(
        name="portfolio_kb",
        metadata={"description": "Ajay Singh's Portfolio Knowledge Base"}
    )
    print(f"Collection 'portfolio_kb' ready. Current document count: {collection.count()}")
except Exception as e:
    print(f"Error creating collection: {e}")

# Start HTTP server
from chromadb.utils import embedding_functions
import uvicorn
from chromadb.server.fastapi import FastAPI

# Note: Chroma 0.4.0+ has built-in HTTP server
# Run with: chromadb run --path "YOUR_GOOGLE_DRIVE_PATH" --port 8000

print("\n" + "="*60)
print("CHROMA SERVER CONFIGURATION")
print("="*60)
print(f"Host: http://localhost:8000")
print(f"Collection: portfolio_kb")
print(f"Storage: {GOOGLE_DRIVE_PATH}")
print("="*60)
print("\nTo start the server, run:")
print(f'chromadb run --path "{GOOGLE_DRIVE_PATH}" --port 8000')
print("\nOR if using Docker:")
print(f'docker run -p 8000:8000 -v "{GOOGLE_DRIVE_PATH}:/chroma/chroma" chromadb/chroma')
print("="*60)
