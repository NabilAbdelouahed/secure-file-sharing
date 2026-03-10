/**
 * Client-side AES-256-GCM file encryption/decryption using the Web Crypto API.
 * The encryption key never leaves the browser — the server only stores ciphertext.
 *
 * Encrypted format: [12-byte IV][AES-GCM ciphertext + 16-byte auth tag]
 * Key transport:    base64url-encoded raw key placed in the URL fragment (#)
 */

function arrayBufferToBase64url(buffer) {
  var bytes = new Uint8Array(buffer);
  var binary = '';
  for (var i = 0; i < bytes.length; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return btoa(binary)
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/, '');
}

function base64urlToArrayBuffer(base64url) {
  var base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
  while (base64.length % 4) base64 += '=';
  var binary = atob(base64);
  var bytes = new Uint8Array(binary.length);
  for (var i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
}

/**
 * Encrypt a File object with a fresh AES-256-GCM key.
 * @param {File} file
 * @returns {Promise<{encryptedBlob: Blob, keyString: string}>}
 */
async function encryptFile(file) {
  var key = await crypto.subtle.generateKey(
    { name: 'AES-GCM', length: 256 },
    true,
    ['encrypt']
  );

  var iv = crypto.getRandomValues(new Uint8Array(12));
  var fileData = await file.arrayBuffer();

  var encrypted = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv: iv },
    key,
    fileData
  );

  var combined = new Uint8Array(iv.length + encrypted.byteLength);
  combined.set(iv);
  combined.set(new Uint8Array(encrypted), iv.length);

  var rawKey = await crypto.subtle.exportKey('raw', key);
  var keyString = arrayBufferToBase64url(rawKey);

  return {
    encryptedBlob: new Blob([combined], { type: 'application/octet-stream' }),
    keyString: keyString,
  };
}

/**
 * Decrypt an ArrayBuffer that was produced by encryptFile().
 * @param {ArrayBuffer} encryptedBuffer
 * @param {string}      keyString  base64url-encoded AES key
 * @returns {Promise<ArrayBuffer>}
 */
async function decryptFile(encryptedBuffer, keyString) {
  var keyBytes = base64urlToArrayBuffer(keyString);
  var key = await crypto.subtle.importKey(
    'raw',
    keyBytes,
    { name: 'AES-GCM' },
    false,
    ['decrypt']
  );

  var data = new Uint8Array(encryptedBuffer);
  var iv = data.slice(0, 12);
  var ciphertext = data.slice(12);

  return crypto.subtle.decrypt(
    { name: 'AES-GCM', iv: iv },
    key,
    ciphertext
  );
}
