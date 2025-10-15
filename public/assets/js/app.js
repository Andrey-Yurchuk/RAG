// RAG System Frontend Application
class RAGApp {
    constructor() {
        this.API_BASE = window.location.origin;
        this.currentTab = 'query';
        this.sessionToken = null;
        this.currentUser = null;
        this.init();
    }

    init() {
        // Проверяем авторизацию при загрузке
        this.checkAuthStatus();
    }

    // Authentication Management
    async checkAuthStatus() {
        const token = localStorage.getItem('session_token');
        
        if (!token) {
            this.showLoginForm();
            return;
        }

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/auth/me`, {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                this.sessionToken = token;
                this.currentUser = data.data.user;
                this.showMainApp();
                this.setupEventListeners();
                this.checkSystemStatus();
                this.loadDocuments();
                this.loadHistory();
                this.setupFileUpload();
            } else {
                // Токен недействителен
                localStorage.removeItem('session_token');
                this.showLoginForm();
            }
        } catch (error) {
            console.error('Auth check failed:', error);
            localStorage.removeItem('session_token');
            this.showLoginForm();
        }
    }

    showLoginForm() {
        document.getElementById('loginContainer').style.display = 'flex';
        document.getElementById('mainApp').style.display = 'none';
        
        // Скрываем ошибки при показе формы
        this.hideLoginError();
        
        // Добавляем обработчик формы логина
        const loginForm = document.getElementById('loginForm');
        if (loginForm && !loginForm.hasAttribute('data-listener-added')) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                this.login(username, password);
            });
            
            // Добавляем обработчики для скрытия ошибок при вводе
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', () => this.hideLoginError());
            }
            if (passwordInput) {
                passwordInput.addEventListener('input', () => this.hideLoginError());
            }
            
            loginForm.setAttribute('data-listener-added', 'true');
        }
    }

    showMainApp() {
        document.getElementById('loginContainer').style.display = 'none';
        document.getElementById('mainApp').style.display = 'block';
        
        // Обновляем информацию о пользователе
        if (this.currentUser) {
            document.getElementById('userName').textContent = `Username: ${this.currentUser.username}`;
            document.getElementById('userRole').textContent = `Role: ${this.currentUser.role.toLowerCase()}`;
        }
    }

    async login(username, password) {
        // Скрываем предыдущие ошибки
        this.hideLoginError();
        
        // Показываем индикатор загрузки
        this.showLoginLoading(true);
        
        try {
            const response = await fetch(`${this.API_BASE}/api/v1/auth/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ username, password })
            });

            const data = await response.json();

            if (data.success) {
                this.sessionToken = data.data.session_token;
                this.currentUser = data.data.user;
                localStorage.setItem('session_token', this.sessionToken);
                
                this.showMainApp();
                this.setupEventListeners();
                this.checkSystemStatus();
                this.loadDocuments();
                this.loadHistory();
                this.setupFileUpload();
                
                this.showToast('Вход выполнен успешно!', 'success');
                return true;
            } else {
                // Отображаем ошибку в форме логина
                this.showLoginError(data.message || 'Ошибка авторизации');
                return false;
            }
        } catch (error) {
            console.error('Login error:', error);
            // Отображаем ошибку в форме логина
            this.showLoginError(`Ошибка: ${error.message}`);
            return false;
        } finally {
            // Скрываем индикатор загрузки
            this.showLoginLoading(false);
        }
    }

    async logout() {
        try {
            if (this.sessionToken) {
                await fetch(`${this.API_BASE}/api/v1/auth/logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.sessionToken}`,
                        'Content-Type': 'application/json'
                    }
                });
            }
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.sessionToken = null;
            this.currentUser = null;
            localStorage.removeItem('session_token');
            this.showLoginForm();
        }
    }

    // Event Listeners Setup
    setupEventListeners() {
        // Tab navigation
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.switchTab(e.target.dataset.tab);
            });
        });

        // Query form
        document.getElementById('queryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitQuery();
        });

        // Text document form
        document.getElementById('textForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitTextDocument();
        });

        // File upload form
        document.getElementById('fileForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitFileUpload();
        });

        // Auto-resize textarea
        const textarea = document.getElementById('queryInput');
        textarea.addEventListener('input', (e) => {
            e.target.style.height = 'auto';
            e.target.style.height = e.target.scrollHeight + 'px';
        });
    }

    // Tab Management
    switchTab(tabName) {
        // Update active tab
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update active content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');

        this.currentTab = tabName;

        // Load data for specific tabs
        if (tabName === 'documents') {
            this.loadDocuments();
        } else if (tabName === 'history') {
            this.loadHistory();
        }
    }

    // System Status Check
    async checkSystemStatus() {
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');

        try {
            const response = await fetch(`${this.API_BASE}/health`);
            const data = await response.json();

            if (data.status === 'healthy') {
                statusDot.className = 'status-dot online';
                statusText.textContent = 'Система работает';
            } else {
                throw new Error('System not healthy');
            }
        } catch (error) {
            statusDot.className = 'status-dot offline';
            statusText.textContent = 'Система недоступна';
        }
    }

    // Query Processing
    async submitQuery() {
        const queryInput = document.getElementById('queryInput');
        const queryText = queryInput.value.trim();

        if (!queryText) {
            this.showToast('Пожалуйста, введите вопрос', 'error');
            return;
        }

        const loadingElement = document.getElementById('queryLoading');
        const resultsSection = document.getElementById('resultsSection');

        // Show loading state
        loadingElement.style.display = 'block';
        resultsSection.style.display = 'none';

        // Start timing
        const startTime = performance.now();

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/query`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.sessionToken}`
                },
                body: JSON.stringify({ 
                    query: queryText,
                    response_time: null // Will be calculated after response
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Calculate response time
                const endTime = performance.now();
                const responseTime = (endTime - startTime) / 1000;
                
                // Update the query with response time
                await this.updateQueryWithResponseTime(data.data.query_id, responseTime);
                
                this.displayQueryResult(data.data, responseTime);
                this.showToast('Ответ получен успешно!', 'success');
            } else {
                throw new Error(data.message || 'Ошибка при обработке запроса');
            }
        } catch (error) {
            this.showToast(`Ошибка: ${error.message}`, 'error');
        } finally {
            loadingElement.style.display = 'none';
        }
    }

    displayQueryResult(data, responseTime = null) {
        const resultsSection = document.getElementById('resultsSection');
        const resultContent = document.getElementById('resultContent');
        const resultMeta = document.getElementById('resultMeta');

        resultContent.textContent = data.response;
        
        let metaHTML = `
            <i class="fas fa-clock"></i> ${data.created_at}
            <span style="margin-left: 1rem;">
                <i class="fas fa-fingerprint"></i> ID: ${data.query_id}
            </span>
        `;
        
        if (responseTime !== null) {
            metaHTML += `
                <span style="margin-left: 1rem;">
                    <i class="fas fa-stopwatch"></i> ${responseTime.toFixed(1)} сек
                </span>
            `;
        }

        resultMeta.innerHTML = metaHTML;
        resultsSection.style.display = 'block';
    }

    async updateQueryWithResponseTime(queryId, responseTime) {
        try {
            const response = await fetch(`${this.API_BASE}/api/v1/query/${queryId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.sessionToken}`
                },
                body: JSON.stringify({ response_time: responseTime }),
            });
            
            const data = await response.json();
        } catch (error) {
            console.warn('Failed to update response time:', error);
        }
    }

    // Document Management
    async loadDocuments() {
        const grid = document.getElementById('documentsGrid');

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/documents`, {
                headers: {
                    'Authorization': `Bearer ${this.sessionToken}`
                }
            });
            const data = await response.json();

            if (data.success) {
                this.displayDocuments(data.data);
            } else {
                throw new Error(data.message || 'Ошибка при загрузке документов');
            }
        } catch (error) {
            grid.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Ошибка при загрузке документов: ${error.message}</p>
                </div>
            `;
        }
    }

    displayDocuments(documents) {
        const grid = document.getElementById('documentsGrid');

        if (documents.length === 0) {
            grid.innerHTML = `
                <div class="empty-message">
                    <i class="fas fa-folder-open"></i>
                    <p>Документы не найдены</p>
                    <small>Добавьте документы через вкладку "Загрузка"</small>
                </div>
            `;
            return;
        }

        grid.innerHTML = documents.map(doc => `
            <div class="document-card" data-id="${doc.id}">
                <div class="document-title">${this.escapeHtml(doc.title)}</div>
                <div class="document-content">${this.escapeHtml(doc.content)}</div>
                <div class="document-meta">
                    <span><i class="fas fa-calendar"></i> ${doc.created_at}</span>
                    <span><i class="fas fa-file-${doc.file_type || 'text'}"></i> ${doc.file_type || 'Текст'}</span>
                </div>
                <div class="document-actions">
                    <button class="btn btn-danger btn-small" onclick="app.deleteDocument('${doc.id}')">
                        <i class="fas fa-trash"></i> Удалить
                    </button>
                </div>
            </div>
        `).join('');
    }

    async deleteDocument(id) {
        if (!confirm('Вы уверены, что хотите удалить этот документ?')) {
            return;
        }

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/documents/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${this.sessionToken}`
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Документ удален успешно!', 'success');
                this.loadDocuments();
            } else {
                throw new Error(data.message || 'Ошибка при удалении документа');
            }
        } catch (error) {
            this.showToast(`Ошибка: ${error.message}`, 'error');
        }
    }

    // Text Document Upload
    async submitTextDocument() {
        const title = document.getElementById('docTitle').value.trim();
        const content = document.getElementById('docContent').value.trim();

        if (!title || !content) {
            this.showToast('Пожалуйста, заполните все поля', 'error');
            return;
        }

        const submitBtn = document.getElementById('textForm').querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Добавление...';

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/documents`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.sessionToken}`
                },
                body: JSON.stringify({ title, content }),
            });

            const data = await response.json();

            if (data.success) {
                this.showToast('Документ добавлен успешно!', 'success');
                document.getElementById('textForm').reset();
                this.loadDocuments();
            } else {
                throw new Error(data.message || 'Ошибка при добавлении документа');
            }
        } catch (error) {
            console.error('Text upload error:', error);
            this.showToast(`Ошибка: ${error.message}`, 'error');
        } finally {
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }

    // File Upload Setup
    setupFileUpload() {
        // Set up event delegation for file input to avoid duplicate listeners
        this.setupFileEventDelegation();
    }

    // Setup event delegation for file upload functionality
    setupFileEventDelegation() {
        const fileForm = document.getElementById('fileForm');

        // Use event delegation on the form to handle dynamic elements
        fileForm.addEventListener('click', (e) => {
            const fileDropZone = document.getElementById('fileDropZone');
            if (e.target.closest('#fileDropZone')) {
                const fileInput = document.getElementById('fileInput');
                if (fileInput) {
                    fileInput.click();
                }
            }
        });

        fileForm.addEventListener('change', (e) => {
            if (e.target.id === 'fileInput') {
                const file = e.target.files[0];
                if (file) {
                    this.updateFileDropZone(file);
                    const submitBtn = fileForm.querySelector('button[type="submit"]');
                    submitBtn.disabled = false;
                }
            }
        });

        // Setup drag and drop on the form level
        fileForm.addEventListener('dragover', (e) => {
            if (e.target.closest('#fileDropZone')) {
                e.preventDefault();
                const fileDropZone = document.getElementById('fileDropZone');
                fileDropZone.classList.add('dragover');
            }
        });

        fileForm.addEventListener('dragleave', (e) => {
            if (e.target.closest('#fileDropZone')) {
                const fileDropZone = document.getElementById('fileDropZone');
                fileDropZone.classList.remove('dragover');
            }
        });

        fileForm.addEventListener('drop', (e) => {
            if (e.target.closest('#fileDropZone')) {
                e.preventDefault();
                const fileDropZone = document.getElementById('fileDropZone');
                fileDropZone.classList.remove('dragover');

                const file = e.dataTransfer.files[0];
                if (file) {
                    const fileInput = document.getElementById('fileInput');
                    if (fileInput) {
                        // Create a new FileList and assign it to the input
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        fileInput.files = dt.files;

                        this.updateFileDropZone(file);
                        const submitBtn = fileForm.querySelector('button[type="submit"]');
                        submitBtn.disabled = false;
                    }
                }
            }
        });
    }

    resetFileDropZone() {
        const fileDropZone = document.getElementById('fileDropZone');
        const fileForm = document.getElementById('fileForm');
        const submitBtn = fileForm ? fileForm.querySelector('button[type="submit"]') : null;

        if (fileDropZone) {
            fileDropZone.innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Перетащите файл сюда или нажмите для выбора</p>
                <small>Поддерживаемые форматы: TXT, MD, HTML, PDF, DOCX, DOC</small>
            `;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
        }

        // Clear the file input
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.value = '';
        }
    }

    updateFileDropZone(file) {
        const fileDropZone = document.getElementById('fileDropZone');

        if (!fileDropZone) {
            console.error('fileDropZone not found in updateFileDropZone');
            return;
        }

        const fileSize = (file.size / 1024).toFixed(2);

        fileDropZone.innerHTML = `
            <i class="fas fa-file-check"></i>
            <p><strong>${file.name}</strong></p>
            <small>${fileSize} KB • ${file.type || 'Неизвестный тип'}</small>
        `;
    }

    async submitFileUpload() {
        const fileInput = document.getElementById('fileInput');

        if (!fileInput) {
            console.error('fileInput not found in DOM');
            this.showToast('Ошибка: элемент загрузки файла не найден', 'error');
            return;
        }

        const file = fileInput.files[0];

        if (!file) {
            this.showToast('Пожалуйста, выберите файл', 'error');
            return;
        }

        const fileDropZone = document.getElementById('fileDropZone');
        const fileForm = document.getElementById('fileForm');
        const submitBtn = fileForm ? fileForm.querySelector('button[type="submit"]') : null;

        if (!fileDropZone) {
            console.error('fileDropZone not found in DOM');
            this.showToast('Ошибка: элемент загрузки не найден', 'error');
            return;
        }

        if (!submitBtn) {
            console.error('submitBtn not found in DOM');
            this.showToast('Ошибка: кнопка загрузки не найдена', 'error');
            return;
        }

        // Show upload progress in the drop zone
        fileDropZone.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            <p><strong>Загрузка файла...</strong></p>
            <small>${file.name} • ${(file.size / 1024).toFixed(2)} KB</small>
        `;

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await fetch(`${this.API_BASE}/api/v1/documents/upload`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.sessionToken}`
                },
                body: formData,
            });

            // Check if response is JSON
            const contentType = response.headers.get('Content-Type');
            if (!contentType || !contentType.includes('application/json')) {
                const responseText = await response.text();
                console.error('Non-JSON response:', responseText);
                throw new Error(`Сервер вернул неожиданный ответ. Status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.showToast('Файл загружен успешно!', 'success');
                this.resetFileDropZone();
                this.loadDocuments();
            } else {
                throw new Error(data.message || 'Ошибка при загрузке файла');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showToast(`Ошибка: ${error.message}`, 'error');
            this.resetFileDropZone();
        } finally {
            // Reset button state - with null check
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Загрузить файл';
            }
        }
    }

    // History Management
    async loadHistory() {
        const historyList = document.getElementById('historyList');

        try {
            const response = await fetch(`${this.API_BASE}/api/v1/queries`, {
                headers: {
                    'Authorization': `Bearer ${this.sessionToken}`
                }
            });
            const data = await response.json();

            if (data.success) {
                this.displayHistory(data.data);
            } else {
                throw new Error(data.message || 'Ошибка при загрузке истории');
            }
        } catch (error) {
            historyList.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Ошибка при загрузке истории: ${error.message}</p>
                </div>
            `;
        }
    }

    displayHistory(queries) {
        const historyList = document.getElementById('historyList');

        if (queries.length === 0) {
            historyList.innerHTML = `
                <div class="empty-message">
                    <i class="fas fa-history"></i>
                    <p>История запросов пуста</p>
                    <small>Задайте вопрос через вкладку "Поиск"</small>
                </div>
            `;
            return;
        }

        historyList.innerHTML = queries.map(query => `
            <div class="history-item">
                <div class="history-query">
                    <i class="fas fa-question-circle"></i>
                    ${this.escapeHtml(query.query_text)}
                </div>
                <div class="history-response">
                    ${this.escapeHtml(query.response)}
                </div>
                <div class="history-meta">
                    <span><i class="fas fa-clock"></i> ${query.created_at}</span>
                    <span style="margin-left: 1rem;">
                        <i class="fas fa-fingerprint"></i> ${query.query_id}
                    </span>
                    ${query.response_time ? `
                        <span style="margin-left: 1rem;">
                            <i class="fas fa-stopwatch"></i> ${parseFloat(query.response_time).toFixed(1)} сек
                        </span>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    // Login Error Management
    showLoginError(message) {
        const errorElement = document.getElementById('loginError');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    hideLoginError() {
        const errorElement = document.getElementById('loginError');
        if (errorElement) {
            errorElement.style.display = 'none';
            errorElement.textContent = '';
        }
    }

    showLoginLoading(show) {
        const loadingElement = document.getElementById('loginLoading');
        const submitButton = document.querySelector('.btn-login');
        
        if (loadingElement) {
            loadingElement.style.display = show ? 'block' : 'none';
        }
        
        if (submitButton) {
            submitButton.disabled = show;
        }
    }

    // Toast Notifications
    showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'info': 'info-circle',
            'warning': 'exclamation-triangle'
        };
        return icons[type] || 'info-circle';
    }

    // Utility Functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleString('ru-RU');
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new RAGApp();
});

// Global functions for HTML onclick handlers
window.loadDocuments = () => window.app.loadDocuments();
window.loadHistory = () => window.app.loadHistory();
window.logout = () => window.app.logout();