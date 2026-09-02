/**
 * Portfolio Chatbot Widget JavaScript
 */

class PortfolioChatbot {
    constructor() {
        this.isOpen = false;
        this.sessionId = this.generateSessionId();
        this.messages = [];
        this.isOnline = false;
        this.healthCheckInterval = null;
        this.isDragging = false;
        this.isResizing = false;
        this.dragOffset = { x: 0, y: 0 };
        this.resizeStart = { x: 0, y: 0, width: 0, height: 0 };
        this.positionOffset = null; // Track position relative to viewport edges
        this.init();
    }
    
    init() {
        this.createChatWidget();
        this.attachEventListeners();
        //this.startHealthMonitoring(); // Start periodic checks
        this.checkHealth(); // Initial health check only
        
        // Open chatbot by default after a short delay
        setTimeout(() => {
            this.toggleChat();
        }, 500);
    }
    
    createChatWidget() {
        // Create chat button
        const button = document.createElement('button');
        button.id = 'chatbot-button';
        button.innerHTML = '<img src="image/me.png" alt="Chat" />';
        button.setAttribute('aria-label', 'Open chatbot');
        
        // Create chat container
        const container = document.createElement('div');
        container.id = 'chatbot-container';
        container.innerHTML = `
            <div class="chatbot-header">
                <div>
                    <h3>Portfolio Assistant</h3>
                    <div class="chatbot-status">
                        <span class="status-dot"></span>
                        <span>Checking...</span>
                    </div>
                </div>
                <button class="chatbot-close" aria-label="Close chatbot">×</button>
            </div>
            
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="welcome-message">
                    <h4>👋 Hello!</h4>
                    <p>I'm Ajay Singh. Ask me anything about my experience, skills, or projects!</p>
                    <div class="quick-questions">
                        <button class="quick-question-btn" data-question="What is your experience with SQL?">What is your experience with SQL?</button>
                        <button class="quick-question-btn" data-question="Tell me about your recent projects">Tell me about your recent projects</button>
                        <button class="quick-question-btn" data-question="What technologies do you work with?">What technologies do you work with?</button>
                    </div>
                </div>
                
                <div class="chat-message bot typing-indicator">
                    <div class="message-avatar bot">
                        <img src="image/me.png" alt="Assistant" />
                    </div>
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
            
            <div class="chatbot-input">
                <input type="text" id="chatbot-input-field" placeholder="Ask me anything..." autocomplete="off">
                <button id="chatbot-send-btn" aria-label="Send message">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                </button>
            </div>
            <div class="chatbot-resize-handle"></div>
        `;
        
        document.body.appendChild(button);
        document.body.appendChild(container);
        
        this.elements = {
            button: button,
            container: container,
            messagesContainer: document.getElementById('chatbot-messages'),
            inputField: document.getElementById('chatbot-input-field'),
            sendButton: document.getElementById('chatbot-send-btn'),
            closeButton: container.querySelector('.chatbot-close'),
            typingIndicator: container.querySelector('.typing-indicator')
        };
    }
    
    attachEventListeners() {
        // Toggle chatbot
        this.elements.button.addEventListener('click', () => this.toggleChat());
        this.elements.closeButton.addEventListener('click', () => this.toggleChat());
        
        // Send message
        this.elements.sendButton.addEventListener('click', () => this.sendMessage());
        this.elements.inputField.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
        
        // Quick questions
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quick-question-btn')) {
                const question = e.target.getAttribute('data-question');
                this.sendMessage(question);
            }
        });
        
        // Drag functionality
        const header = this.elements.container.querySelector('.chatbot-header');
        header.addEventListener('mousedown', (e) => this.startDrag(e));
        
        // Resize functionality
        const resizeHandle = this.elements.container.querySelector('.chatbot-resize-handle');
        resizeHandle.addEventListener('mousedown', (e) => this.startResize(e));
        
        // Global mouse events
        document.addEventListener('mousemove', (e) => {
            if (this.isDragging) this.drag(e);
            if (this.isResizing) this.resize(e);
        });
        document.addEventListener('mouseup', () => {
            this.stopDrag();
            this.stopResize();
        });
        
        // Window resize handler
        window.addEventListener('resize', () => this.handleWindowResize());
    }
    
    handleWindowResize() {
        // Only adjust if chatbot has been manually positioned and is currently open
        if (!this.positionOffset || !this.isOpen) return;
        
        const rect = this.elements.container.getBoundingClientRect();
        
        // Calculate new position maintaining the offset from right/bottom edges
        let newLeft = window.innerWidth - this.positionOffset.fromRight - rect.width;
        let newTop = window.innerHeight - this.positionOffset.fromBottom - rect.height;
        
        // Ensure it stays within viewport bounds
        const maxX = window.innerWidth - rect.width;
        const maxY = window.innerHeight - rect.height;
        
        newLeft = Math.max(0, Math.min(newLeft, maxX));
        newTop = Math.max(0, Math.min(newTop, maxY));
        
        this.elements.container.style.left = newLeft + 'px';
        this.elements.container.style.top = newTop + 'px';
    }
    
    startDrag(e) {
        // Don't start drag if clicking on close button
        if (e.target.classList.contains('chatbot-close')) return;
        
        this.isDragging = true;
        const rect = this.elements.container.getBoundingClientRect();
        this.dragOffset = {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
        this.elements.container.style.transition = 'none';
        e.preventDefault();
    }
    
    drag(e) {
        if (!this.isDragging) return;
        
        const x = e.clientX - this.dragOffset.x;
        const y = e.clientY - this.dragOffset.y;
        
        // Keep within viewport
        const maxX = window.innerWidth - this.elements.container.offsetWidth;
        const maxY = window.innerHeight - this.elements.container.offsetHeight;
        
        const boundedX = Math.max(0, Math.min(x, maxX));
        const boundedY = Math.max(0, Math.min(y, maxY));
        
        this.elements.container.style.left = boundedX + 'px';
        this.elements.container.style.top = boundedY + 'px';
        this.elements.container.style.right = 'auto';
        this.elements.container.style.bottom = 'auto';
        
        // Save position offset from right and bottom edges
        this.positionOffset = {
            fromRight: window.innerWidth - boundedX - this.elements.container.offsetWidth,
            fromBottom: window.innerHeight - boundedY - this.elements.container.offsetHeight
        };
    }
    
    stopDrag() {
        this.isDragging = false;
        this.elements.container.style.transition = '';
    }
    
    startResize(e) {
        this.isResizing = true;
        this.resizeStart = {
            x: e.clientX,
            y: e.clientY,
            width: this.elements.container.offsetWidth,
            height: this.elements.container.offsetHeight
        };
        e.preventDefault();
        e.stopPropagation();
    }
    
    resize(e) {
        if (!this.isResizing) return;
        
        const deltaX = e.clientX - this.resizeStart.x;
        const deltaY = e.clientY - this.resizeStart.y;
        
        let newWidth = this.resizeStart.width + deltaX;
        let newHeight = this.resizeStart.height + deltaY;
        
        // Apply min/max constraints
        newWidth = Math.max(300, Math.min(600, newWidth));
        newHeight = Math.max(400, Math.min(800, newHeight));
        
        this.elements.container.style.width = newWidth + 'px';
        this.elements.container.style.height = newHeight + 'px';
    }
    
    stopResize() {
        this.isResizing = false;
    }
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        this.elements.container.classList.toggle('active', this.isOpen);
        this.elements.button.classList.toggle('active', this.isOpen);
        
        if (this.isOpen) {
            this.elements.inputField.focus();
            this.scrollToBottom();
        }
    }
    
    async sendMessage(messageText = null) {
        const message = messageText || this.elements.inputField.value.trim();
        
        if (!message) return;
        
        // Clear input
        this.elements.inputField.value = '';
        
        // Add user message to UI
        this.addMessage('user', message);

        // Add a placeholder for the assistant response so it can fill in live
        const botMessageElement = this.addMessage('bot', '');
        const botMessageContent = botMessageElement.querySelector('.message-content');
        
        // Show typing indicator
        this.showTyping(true);
        
        // Disable input while processing
        this.setInputState(false);
        
        try {
            // Send to API using configured base path
            const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
            const response = await fetch(`${apiBasePath}/api/chat.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream'
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId,
                    stream: true
                }),
                cache: 'no-store'
            });

            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('text/event-stream')) {
                const reader = response.body?.getReader();
                if (!reader) {
                    throw new Error('Streaming response is not available.');
                }

                const decoder = new TextDecoder();
                let buffer = '';
                let replyText = '';
                let receivedFirstChunk = false;

                while (true) {
                    const { value, done } = await reader.read();

                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });

                    let separatorIndex;
                    while ((separatorIndex = buffer.indexOf('\n\n')) !== -1) {
                        const rawEvent = buffer.slice(0, separatorIndex);
                        buffer = buffer.slice(separatorIndex + 2);
                        const streamEvent = this.parseStreamEvent(rawEvent);

                        if (!streamEvent) {
                            continue;
                        }

                        if (streamEvent.event === 'delta' && streamEvent.data?.text) {
                            replyText += streamEvent.data.text;
                            botMessageContent.innerHTML = this.formatBotMessage(replyText);

                            if (!receivedFirstChunk) {
                                this.showTyping(false);
                                receivedFirstChunk = true;
                            }

                            this.scrollToBottom();
                        }

                        if (streamEvent.event === 'done') {
                            if (streamEvent.data?.reply && !replyText) {
                                replyText = streamEvent.data.reply;
                                botMessageContent.innerHTML = this.formatBotMessage(replyText);
                            }

                            if (streamEvent.data?.response_time_ms) {
                                console.log(`Response time: ${streamEvent.data.response_time_ms}ms`);
                            }

                            this.messages[this.messages.length - 1] = {
                                role: 'bot',
                                content: replyText,
                                sources: streamEvent.data?.sources || []
                            };
                        }

                        if (streamEvent.event === 'error') {
                            throw new Error(streamEvent.data?.reply || streamEvent.data?.message || 'Failed to get response');
                        }
                    }
                }

                if (!receivedFirstChunk) {
                    this.showTyping(false);
                }

                if (!replyText) {
                    throw new Error('Empty streamed response');
                }
            } else {
                const data = await response.json();

                // Hide typing indicator
                this.showTyping(false);

                if (data.status === 'success') {
                    botMessageContent.innerHTML = this.formatBotMessage(data.reply);
                    this.messages[this.messages.length - 1] = {
                        role: 'bot',
                        content: data.reply,
                        sources: data.sources || []
                    };

                    // Log response time if available
                    if (data.response_time_ms) {
                        console.log(`Response time: ${data.response_time_ms}ms`);
                    }
                } else {
                    throw new Error(data.message || 'Failed to get response');
                }
            }
            
        } catch (error) {
            console.error('Chat error:', error);
            this.showTyping(false);
            botMessageContent.innerHTML = this.formatBotMessage('Sorry, I encountered an error. Please try again.');
        } finally {
            // Re-enable input
            this.setInputState(true);
            this.elements.inputField.focus();
        }
    }
    
    addMessage(role, content, sources = null) {
        // Remove welcome message on first interaction
        const welcomeMsg = this.elements.messagesContainer.querySelector('.welcome-message');
        if (welcomeMsg && this.messages.length === 0) {
            welcomeMsg.remove();
        }
        
        this.messages.push({ role, content, sources });
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${role}`;
        
        const avatar = document.createElement('div');
        avatar.className = `message-avatar ${role}`;
        
        if (role === 'bot') {
            avatar.innerHTML = '<img src="image/me.png" alt="Assistant" />';
        } else {
            avatar.innerHTML = '👤';
        }
        
        const messageContent = document.createElement('div');
        messageContent.className = 'message-content';
        
        // Format content for better display
        if (role === 'bot') {
            messageContent.innerHTML = this.formatBotMessage(content);
        } else {
            messageContent.textContent = content;
        }
        
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(messageContent);
        
        // Insert before typing indicator
        this.elements.messagesContainer.insertBefore(
            messageDiv,
            this.elements.typingIndicator
        );
        
        this.scrollToBottom();

        return messageDiv;
    }

    parseStreamEvent(rawEvent) {
        const lines = rawEvent.split(/\r?\n/);
        let event = 'message';
        const dataLines = [];

        for (const line of lines) {
            if (line.startsWith('event:')) {
                event = line.slice(6).trim();
            } else if (line.startsWith('data:')) {
                dataLines.push(line.slice(5).trimStart());
            }
        }

        const dataText = dataLines.join('\n').trim();
        if (!dataText) {
            return null;
        }

        let data = dataText;
        try {
            data = JSON.parse(dataText);
        } catch (error) {
            // Keep raw text if the event payload is not JSON.
        }

        return { event, data };
    }
    
    formatBotMessage(text) {
        // Convert markdown-like formatting to HTML
        let formatted = text;
        
        // Convert **bold** to <strong>
        formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Convert bullet points (- or • at start of line)
        formatted = formatted.replace(/^[•\-]\s+(.+)$/gm, '<li>$1</li>');
        
        // Wrap consecutive <li> items in <ul>
        formatted = formatted.replace(/(<li>.*<\/li>\s*)+/gs, '<ul>$&</ul>');
        
        // Convert numbered lists (1. 2. 3. etc)
        formatted = formatted.replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>');
        formatted = formatted.replace(/(<li>.*<\/li>\s*)+/gs, (match) => {
            if (match.includes('<ul>')) return match; // Already wrapped
            return '<ol>' + match + '</ol>';
        });
        
        // Convert line breaks (double newline = paragraph, single = br)
        formatted = formatted.replace(/\n\n/g, '</p><p>');
        formatted = formatted.replace(/\n/g, '<br>');
        formatted = '<p>' + formatted + '</p>';
        
        // Clean up empty paragraphs
        formatted = formatted.replace(/<p><\/p>/g, '');
        formatted = formatted.replace(/<p>\s*<\/p>/g, '');
        
        return formatted;
    }
    
    showTyping(show) {
        this.elements.typingIndicator.classList.toggle('active', show);
        if (show) {
            this.scrollToBottom();
        }
    }
    
    setInputState(enabled) {
        this.elements.inputField.disabled = !enabled;
        this.elements.sendButton.disabled = !enabled;
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.elements.messagesContainer.scrollTop = 
                this.elements.messagesContainer.scrollHeight;
        }, 100);
    }
    
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    async checkHealth() {
        try {
            // Add timestamp to prevent caching and use configured base path
            const apiBasePath = window.PORTFOLIO_CONFIG?.apiBasePath || '/portfolio';
            const response = await fetch(`${apiBasePath}/api/health.php?t=${Date.now()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
                cache: 'no-cache'
            });
            
            const data = await response.json();
            console.log('Health check response:', data);
            
            if (data.status === 'success' && data.data) {
                const canChat = data.data.can_chat === true;
                console.log('Overall status:', data.data.overall_status, 'canChat:', canChat);
                this.updateStatus(canChat, data.data.overall_status);
            } else {
                console.log('Health check failed: Invalid response structure');
                this.updateStatus(false, 'offline');
            }
        } catch (error) {
            console.error('Health check failed:', error);
            this.updateStatus(false, 'offline');
        }
    }
    
    updateStatus(isOnline, statusLabel = 'offline') {
        this.isOnline = isOnline;
        
        const statusDot = this.elements.container.querySelector('.status-dot');
        const statusText = this.elements.container.querySelector('.chatbot-status span:last-child');
        
        if (statusDot && statusText) {
            if (isOnline) {
                statusDot.classList.remove('offline');
                statusDot.classList.add('online');
                statusText.textContent = statusLabel === 'degraded' ? 'Degraded' : 'Online';
            } else {
                statusDot.classList.remove('online');
                statusDot.classList.add('offline');
                statusText.textContent = 'Offline';
            }
        }
        
        // Disable input if offline
        if (!isOnline) {
            this.setInputState(false);
            const inputField = this.elements.inputField;
            if (inputField) {
                inputField.placeholder = 'Chatbot is offline...';
            }
        } else {
            this.setInputState(true);
            const inputField = this.elements.inputField;
            if (inputField) {
                inputField.placeholder = 'Ask me anything...';
            }
        }
    }
    
    startHealthMonitoring() {
        // Check health every 30 seconds
        this.healthCheckInterval = setInterval(() => {
            this.checkHealth();
        }, 30000);
    }
    
    stopHealthMonitoring() {
        if (this.healthCheckInterval) {
            clearInterval(this.healthCheckInterval);
            this.healthCheckInterval = null;
        }
    }
}

// Initialize chatbot when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.portfolioChatbot = new PortfolioChatbot();
    });
} else {
    window.portfolioChatbot = new PortfolioChatbot();
}
