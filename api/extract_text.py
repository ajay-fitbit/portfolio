#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Text extraction helper for PDF and DOCX files
Usage: python extract_text.py <filepath>
"""

import sys
import os

# Set UTF-8 encoding for output
if sys.platform == 'win32':
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')

from PyPDF2 import PdfReader
from docx import Document

def sanitize_text(text):
    """Remove or replace problematic characters"""
    # Replace common Unicode bullets and special chars
    replacements = {
        '\u25cf': '•',  # Bullet point
        '\u2022': '•',  # Bullet
        '\u2013': '-',  # En dash
        '\u2014': '-',  # Em dash
        '\u2018': "'",  # Left single quote
        '\u2019': "'",  # Right single quote
        '\u201c': '"',  # Left double quote
        '\u201d': '"',  # Right double quote
        '\xa0': ' ',    # Non-breaking space
    }
    
    for old, new in replacements.items():
        text = text.replace(old, new)
    
    # Remove any remaining non-ASCII characters that might cause issues
    # text = text.encode('ascii', 'ignore').decode('ascii')
    
    return text

def extract_from_pdf(filepath):
    """Extract text from PDF file"""
    try:
        reader = PdfReader(filepath)
        text = ""
        for page in reader.pages:
            text += page.extract_text() + "\n"
        return sanitize_text(text.strip())
    except Exception as e:
        return f"ERROR: {str(e)}"

def extract_from_docx(filepath):
    """Extract text from DOCX file"""
    try:
        doc = Document(filepath)
        text = "\n".join([paragraph.text for paragraph in doc.paragraphs])
        return sanitize_text(text.strip())
    except Exception as e:
        return f"ERROR: {str(e)}"

def extract_from_doc(filepath):
    """Extract text from old DOC format"""
    # For .doc files, we need a different approach
    # Try using textract or convert to DOCX first
    return "ERROR: Old .doc format not supported. Please convert to .docx or .pdf"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("ERROR: No file path provided")
        sys.exit(1)
    
    filepath = sys.argv[1]
    
    if not os.path.exists(filepath):
        print(f"ERROR: File not found: {filepath}")
        sys.exit(1)
    
    ext = os.path.splitext(filepath)[1].lower()
    
    if ext == '.pdf':
        print(extract_from_pdf(filepath))
    elif ext == '.docx':
        print(extract_from_docx(filepath))
    elif ext == '.doc':
        print(extract_from_doc(filepath))
    else:
        print(f"ERROR: Unsupported file type: {ext}")
        sys.exit(1)
