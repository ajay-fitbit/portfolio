# 🚀 AI-Powered Portfolio Chatbot

A full-stack portfolio website featuring an intelligent AI chatbot powered by OpenAI's GPT-4 and Retrieval-Augmented Generation (RAG) with ChromaDB vector store.

## ✨ Features

- **🤖 AI Chatbot**: Conversational interface using OpenAI GPT-4o-mini with streaming responses
- **📚 RAG System**: Retrieval-Augmented Generation for knowledge-base aware responses
- **💾 Vector Database**: ChromaDB for semantic search and document embeddings
- **📱 Responsive Design**: Mobile-friendly portfolio with floating chatbot widget
- **👥 Admin Panel**: Content management system for portfolio updates
- **📊 Analytics**: Chat history and analytics tracking with MySQL
- **🔐 Secure API**: RESTful API endpoints with configuration management
- **⚡ SSE Streaming**: Real-time chat response streaming

## 🗄️ Database Architecture

This application uses **two complementary databases**:

| Database | Purpose | Storage |
|----------|---------|---------|
| **MySQL** | Chat history, analytics, user data, structured information | Relational tables |
| **ChromaDB** | Document embeddings for semantic search, portfolio content vectors | Vector embeddings |

- **MySQL**: Stores historical chat messages, user interactions, analytics metrics
- **ChromaDB**: Stores embeddings of your portfolio content (resume, projects, about info) to enable intelligent retrieval for the RAG system

## 🛠️ Tech Stack

### Backend
- **PHP 7.3+** - Main web application and API endpoints
- **MySQL** - Chat history, analytics, and structured data storage
- **OpenAI API** - GPT-4o-mini for chat, text-embedding-3-small for embeddings

### Vector Database (RAG System)
- **ChromaDB v0.4.22** - Vector database for semantic search of portfolio content
  - Stores document embeddings (resume, projects, about info)
  - Enables intelligent retrieval for chatbot context
  - Separate from MySQL - MySQL stores chat history, ChromaDB stores embeddings
- **Python 3.8+** - Required only for running ChromaDB server
- **FastAPI/Uvicorn** - Python web framework for ChromaDB

### Frontend
- **HTML5 / CSS3** - Responsive design
- **Vanilla JavaScript** - Interactive UI and real-time chat updates
- **CSS Grid/Flexbox** - Modern layout system
- **Font Awesome** - Icon library

### Document Processing
- **PyPDF2** - PDF text extraction (Python)
- **python-docx** - Word document parsing (Python)

## 📋 Project Structure

```
portfolio/
├── api/                          # API endpoints
│   ├── chat.php                 # Main chat endpoint (supports streaming)
│   ├── analytics.php            # Analytics API
│   ├── health.php               # Health check endpoint
│   ├── ingest.php               # Document ingestion
│   ├── config.php               # API configuration
│   ├── chroma_client.php        # ChromaDB client wrapper
│   ├── openai_vector_client.php # OpenAI integration
│   └── extract_text.py          # PDF/DOCX text extraction
├── admin/                        # Admin panel
│   ├── index.php                # Admin dashboard
│   ├── login.php                # Authentication
│   ├── content.php              # Content management
│   ├── projects.php             # Project management
│   └── resume_admin.php         # Resume management
├── includes/                     # Shared PHP includes
│   ├── config.php               # Configuration
│   └── env_loader.php           # Environment variables
├── css/                         # Stylesheets
│   ├── styles.css              # Main styles
│   ├── chatbot.css             # Chatbot widget styles
│   ├── burgerMenu.css          # Mobile menu styles
│   └── mobileView.css          # Mobile responsive styles
├── js/                         # JavaScript
│   ├── chatbot.js              # Chatbot widget logic
│   ├── script.js               # Main site scripts
│   ├── burgerMenu.js           # Mobile menu functionality
│   └── model.js                # Modal dialogs
├── resume/                     # Resume files
├── image/                      # Image assets
├── index.php                   # Home page
├── about.php                   # About page
├── projects.php                # Projects page
├── resume.php                  # Resume page
├── requirements.txt            # Python dependencies
├── start_chroma.bat            # ChromaDB startup script
└── chroma_server.py            # ChromaDB server script
```

## 🚀 Quick Start

### Prerequisites

**Required:**
- PHP 7.3 or higher
- MySQL 5.7 or higher
- OpenAI API key

**For ChromaDB Vector Database:**
- Python 3.8 or higher (only needed if running ChromaDB locally)
- pip package manager

**Optional:**
- Composer (for PHP dependency management)

### Installation

#### 1. Clone Repository
```bash
git clone <repository-url>
cd portfolio
```

#### 2. Install ChromaDB (Vector Database)

This is required for the RAG system to work. Install Python dependencies:

```bash
pip install -r requirements.txt
```

**Note:** These are Python dependencies for ChromaDB only. The main application is PHP-based.

#### 3. Configure Environment Variables
Create a `.env` file in the project root:
```env
OPENAI_API_KEY=sk-your-actual-key-here
CHROMA_URL=http://localhost:8000
CHROMA_COLLECTION=portfolio_kb
LLM_MODEL=gpt-4o-mini
EMBEDDING_MODEL=text-embedding-3-small
CHUNK_SIZE=400
TOP_K_RESULTS=5
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
```

#### 4. Set Up Database
```bash
mysql -u root -p < setup_chatbot_db.sql
```

#### 5. Start ChromaDB Server
```bash
# Windows
start_chroma.bat

# Linux/Mac
chroma run --path ./chroma_data --port 8000 --host 127.0.0.1
```

#### 6. Configure Web Server
Point your web server document root to this directory and ensure PHP and MySQL are running.

#### 7. Access the Application
- Portfolio: `http://localhost/portfolio`
- Admin Panel: `http://localhost/portfolio/admin`

## 📡 API Endpoints

### Chat Endpoint
**POST** `/api/chat.php`

```json
{
  "message": "Tell me about your experience",
  "stream": true
}
```

Response with streaming (SSE format):
```
data: {"type": "delta", "text": "I have..."}
data: {"type": "done", "content": "Full response..."}
```

### Health Check
**GET** `/api/health.php`

```json
{
  "status": "ok",
  "chroma": "connected",
  "openai": "connected"
}
```

### Document Ingestion
**POST** `/api/ingest.php`

```json
{
  "file": "resume.pdf",
  "type": "pdf"
}
```

### Analytics
**GET** `/api/analytics.php`

Returns chat statistics and metrics.

## ⚙️ Configuration

### environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `OPENAI_API_KEY` | - | OpenAI API key (required) |
| `CHROMA_URL` | `http://localhost:8000` | ChromaDB server URL |
| `CHROMA_COLLECTION` | `portfolio_kb` | Vector store collection name |
| `LLM_MODEL` | `gpt-4o-mini` | OpenAI model for chat |
| `EMBEDDING_MODEL` | `text-embedding-3-small` | OpenAI embedding model |
| `CHUNK_SIZE` | `400` | Document chunk size for embeddings |
| `TOP_K_RESULTS` | `5` | Number of relevant docs to retrieve |

### Admin Panel
Access the admin panel at `/admin` with configured credentials to manage:
- Portfolio content
- Projects
- Resume information
- Chat settings

## 🤖 Chatbot Features

### Streaming Responses
The chatbot supports Server-Sent Events (SSE) for real-time streaming of responses:

```javascript
// Frontend example
const response = await fetch('/api/chat.php', {
  method: 'POST',
  body: JSON.stringify({ message: 'Your question', stream: true })
});

const reader = response.body.getReader();
// Process streamed data...
```

### RAG (Retrieval-Augmented Generation)
The chatbot uses RAG to answer questions about your portfolio:

1. **Query Processing**: User question is converted to embeddings using OpenAI's text-embedding-3-small
2. **Semantic Search**: ChromaDB searches for relevant portfolio content based on embeddings (resume, projects, skills, etc.)
3. **Context Augmentation**: Retrieved documents are added as context to the ChatGPT prompt
4. **Response Generation**: OpenAI GPT-4o-mini generates responses grounded in your actual portfolio data

This ensures the chatbot answers questions accurately about YOU, not generic knowledge.
- Responds with your specific experience, projects, and qualifications
- Stays current with updates you make through the admin panel
- Avoids hallucinations by grounding responses in your content


## 🔧 Troubleshooting

### ChromaDB Connection Issues
- Verify ChromaDB is running: `http://localhost:8000/api/v1`
- Check `CHROMA_URL` in configuration
- Review ChromaDB logs for errors

### OpenAI API Errors
- Verify API key is correct and active
- Check rate limits and usage
- Ensure model names are correct

### Chat Not Working
- Run `/api/health.php` to check system status
- Check PHP error logs
- Verify database connection
- Review browser console for JavaScript errors

## 📝 Development

### Adding New Pages
1. Create `.php` file in root directory
2. Include header/footer templates
3. Update navigation menu in `index.php`
4. Add link in admin panel

### Customizing Chatbot
- Edit `js/chatbot.js` for frontend behavior
- Modify `api/chat.php` for backend logic
- Update CSS in `css/chatbot.css` for styling

### Adding New API Endpoints
1. Create new file in `/api` directory
2. Include configuration: `require_once '../includes/config.php'`
3. Implement endpoint logic
4. Return JSON responses

## 🔐 Security

- Protect admin panel with authentication
- Store API keys in environment variables only
- Use HTTPS/SSL in production
- Sanitize user inputs
- Implement rate limiting for API endpoints
- Keep dependencies updated

## 📄 License

[Add your license information here]

## 👤 Author

Ajay Singh - Portfolio & Chatbot Development

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request


---

**Last Updated**: 2026-09-02
