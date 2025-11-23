// ========== AUTHENTICATION ==========
const authOverlay = document.getElementById('authOverlay');
const loginForm = document.getElementById('loginForm');
const loginMsg = document.getElementById('loginMsg');
const mainApp = document.getElementById('mainApp');
const userBadge = document.getElementById('userBadge');
const logoutBtn = document.getElementById('logoutBtn');

let currentUser = null;

function showAuthOverlay() {
  authOverlay.classList.remove('hidden');
  mainApp.style.display = 'none';
}

function hideAuthOverlay() {
  authOverlay.classList.add('hidden');
  mainApp.style.display = 'block';
}

function showLoading(btn) {
  const text = btn.querySelector('.btn-text');
  const loader = btn.querySelector('.btn-loader');
  if (text) text.style.display = 'none';
  if (loader) loader.style.display = 'inline-block';
  btn.disabled = true;
}

function hideLoading(btn) {
  const text = btn.querySelector('.btn-text');
  const loader = btn.querySelector('.btn-loader');
  if (text) text.style.display = 'inline-block';
  if (loader) loader.style.display = 'none';
  btn.disabled = false;
}

loginForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = loginForm.querySelector('button[type="submit"]');
  showLoading(submitBtn);
  loginMsg.textContent = '';
  loginMsg.classList.remove('error', 'success');

  const formData = new FormData(loginForm);

  try {
    const response = await fetch('login.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if (data.status === 'success') {
      currentUser = data.user;
      userBadge.textContent = `👤 ${currentUser}`;
      loginMsg.textContent = 'Login successful!';
      loginMsg.classList.add('success');
      
      setTimeout(() => {
        hideAuthOverlay();
        showToast(data.message, 'success');
        loginForm.reset();
        loginMsg.textContent = '';
      }, 500);
    } else {
      loginMsg.textContent = data.message || 'Login failed';
      loginMsg.classList.add('error');
    }
  } catch (error) {
    console.error('Login error:', error);
    loginMsg.textContent = 'Network error. Please try again.';
    loginMsg.classList.add('error');
  } finally {
    hideLoading(submitBtn);
  }
});

logoutBtn.addEventListener('click', async () => {
  try {
    // Call logout endpoint to destroy session
    await fetch('logout.php', { method: 'POST' });
    currentUser = null;
    userBadge.textContent = '';
    showAuthOverlay();
    showToast('Logged out successfully', 'success');
  } catch (error) {
    console.error('Logout error:', error);
    showToast('Logout failed', 'error');
  }
});

// ========== TAB NAVIGATION ==========
const tabButtons = document.querySelectorAll('.tab-btn');
const tabs = document.querySelectorAll('.tab');

tabButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-tab');
    
    tabButtons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    tabs.forEach(tab => {
      tab.classList.remove('active');
      if (tab.id === target) {
        tab.classList.add('active');
      }
    });
  });
});

// ========== TOAST NOTIFICATIONS ==========
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = `toast ${type}`;
  
  setTimeout(() => toast.classList.add('show'), 100);
  
  setTimeout(() => {
    toast.classList.remove('show');
  }, 3000);
}

// ========== SEND DOCUMENTS - TAG & QR GENERATION ==========
const generateBtn = document.getElementById('generateBtn');
const resetSendBtn = document.getElementById('resetSendBtn');
const sendForm = document.getElementById('portalForm-send');
const qrImage = document.getElementById('qrImage');
const docTagDiv = document.getElementById('docTag');
const docTagInput = document.getElementById('docTagInput');
const generatedArea = document.getElementById('generatedArea');
const copyTagBtn = document.getElementById('copyTagBtn');
const printQrBtn = document.getElementById('printQrBtn');

function generateTag() {
  const timestamp = Date.now().toString(36).toUpperCase();
  const random = Math.random().toString(36).slice(2, 8).toUpperCase();
  return `BULSU-${timestamp}-${random}`;
}

function generateQR(tag) {
  const encodedTag = encodeURIComponent(tag);
  qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodedTag}`;
}

generateBtn.addEventListener('click', () => {
  const tag = generateTag();
  docTagDiv.textContent = tag;
  docTagInput.value = tag;
  generateQR(tag);
  generatedArea.style.display = 'grid';
  showToast('Tag and QR code generated successfully', 'success');
});

resetSendBtn.addEventListener('click', () => {
  sendForm.reset();
  docTagDiv.textContent = '';
  docTagInput.value = '';
  qrImage.src = '';
  generatedArea.style.display = 'none';
});

copyTagBtn.addEventListener('click', async () => {
  const tag = docTagInput.value;
  if (!tag) return;
  
  try {
    await navigator.clipboard.writeText(tag);
    copyTagBtn.textContent = '✓ Copied';
    showToast('Tag copied to clipboard', 'success');
    setTimeout(() => {
      copyTagBtn.textContent = 'Copy Tag';
    }, 2000);
  } catch (error) {
    showToast('Failed to copy tag', 'error');
  }
});

printQrBtn.addEventListener('click', () => {
  const tag = docTagInput.value;
  const src = qrImage.src;
  
  if (!tag || !src) {
    showToast('Generate a tag first', 'warning');
    return;
  }

  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    showToast('Please allow popups to print', 'warning');
    return;
  }

  const printContent = `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="utf-8">
        <title>Print QR - ${tag}</title>
        <style>
          @page { size: letter; margin: 0.5in; }
          * { margin: 0; padding: 0; box-sizing: border-box; }
          body {
            font-family: Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
          }
          .container {
            text-align: center;
            page-break-after: avoid;
          }
          .qr-code {
            width: 300px;
            height: 300px;
            margin: 0 auto 20px;
            border: 2px solid #333;
            border-radius: 8px;
          }
          .tag {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            word-break: break-all;
          }
          .header {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
          }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">BulSU Document Tracker</div>
          <img class="qr-code" src="${src}" alt="QR Code">
          <div class="tag">${tag}</div>
        </div>
      </body>
    </html>
  `;

  printWindow.document.open();
  printWindow.document.write(printContent);
  printWindow.document.close();
  
  printWindow.onload = () => {
    setTimeout(() => {
      printWindow.print();
    }, 250);
  };
});

// ========== FORM SUBMISSIONS ==========
const receiveForm = document.getElementById('portalForm-receive');
const trackForm = document.getElementById('portalForm-track');

sendForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  const submitBtn = sendForm.querySelector('#sendSubmitBtn');
  
  // Validate that tag is generated
  const docTag = docTagInput.value;
  if (!docTag) {
    showToast('Please generate a document tag first', 'warning');
    return;
  }
  
  try {
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    
    const formData = new FormData(sendForm);
    
    // Ensure docTag is included
    if (!formData.has('docTag') || !formData.get('docTag')) {
      formData.set('docTag', docTag);
    }
    
    const response = await fetch('documentSourceCheck.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    
    if (data.status === 'success') {
      showToast(data.message, 'success');
      sendForm.reset();
      docTagDiv.textContent = '';
      docTagInput.value = '';
      qrImage.src = '';
      generatedArea.style.display = 'none';
    } else {
      showToast(data.message || 'Operation failed', 'error');
    }
  } catch (error) {
    console.error('Form submission error:', error);
    showToast('Network error. Please try again.', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit';
  }
});

async function handleFormSubmit(form, endpoint) {
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');
  
  try {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    const response = await fetch(endpoint, {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    
    if (data.status === 'success') {
      showToast(data.message, 'success');
      
      // Show tracking data if available
      if (data.data) {
        displayTrackingResults(data.data);
      }
      
      form.reset();
    } else {
      showToast(data.message || 'Operation failed', 'error');
    }
  } catch (error) {
    console.error('Form submission error:', error);
    showToast('Network error. Please try again.', 'error');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Submit';
  }
}

receiveForm.addEventListener('submit', (e) => {
  e.preventDefault();
  handleFormSubmit(receiveForm, 'documentSourceCheck.php');
});

trackForm.addEventListener('submit', (e) => {
  e.preventDefault();
  handleFormSubmit(trackForm, 'trackDocument.php');
});

// ========== TRACKING RESULTS DISPLAY ==========
function displayTrackingResults(data) {
  const trackSection = document.getElementById('track');
  
  // Remove existing results
  const existingResults = trackSection.querySelector('.tracking-results');
  if (existingResults) {
    existingResults.remove();
  }
  
  // Create results display
  const resultsDiv = document.createElement('div');
  resultsDiv.className = 'tracking-results';
  resultsDiv.innerHTML = `
    <h3>Document Found</h3>
    <div class="result-item">
      <span class="result-label">Document Name:</span>
      <span class="result-value">${escapeHtml(data.documentName)}</span>
    </div>
    <div class="result-item">
      <span class="result-label">Referring To:</span>
      <span class="result-value">${escapeHtml(data.referringTo || 'N/A')}</span>
    </div>
    <div class="result-item">
      <span class="result-label">Date Received:</span>
      <span class="result-value">${escapeHtml(data.dateReceived)}</span>
    </div>
  `;
  
  trackSection.querySelector('.tab-content').appendChild(resultsDiv);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ========== INITIALIZE ==========
document.addEventListener('DOMContentLoaded', async () => {
  // Check if user has active session
  try {
    const response = await fetch('check_session.php');
    const data = await response.json();
    
    if (data.authenticated && data.user) {
      currentUser = data.user;
      userBadge.textContent = `👤 ${currentUser}`;
      hideAuthOverlay();
    } else {
      showAuthOverlay();
    }
  } catch (error) {
    console.error('Session check failed:', error);
    showAuthOverlay();
  }
});