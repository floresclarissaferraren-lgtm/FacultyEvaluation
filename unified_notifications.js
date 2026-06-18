/**
 * Unified Notification System
 * Consistent notification functionality across all pages (Admin, Student, Instructor)
 * 
 * Usage:
 * showNotification("Message text", "success") - Green success notification
 * showNotification("Message text", "error")   - Red error notification
 * showNotification("Message text", "warning") - Orange warning notification
 * showNotification("Message text", "info")    - Blue info notification
 */

(function() {
  'use strict';

  /**
   * Show a notification message with consistent styling
   * @param {string} message - The message to display
   * @param {string} type - Type: 'success', 'error', 'warning', or 'info'
   * @param {number} duration - Duration in milliseconds (default: 3000)
   */
  window.showNotification = function(message, type = 'info', duration = 3000) {
    // Normalize type to handle old color-based calls
    if (type.startsWith('#')) {
      // Convert old color codes to types
      if (type === '#4caf50' || type === '#10b981') type = 'success';
      else if (type === '#f44336' || type === '#dc2626' || type === '#991b1b') type = 'error';
      else if (type === '#f59e0b' || type === '#d97706') type = 'warning';
      else type = 'info';
    }

    // Get or create notification container
    let notification = document.getElementById('globalNotification');
    
    if (!notification) {
      notification = document.createElement('div');
      notification.id = 'globalNotification';
      notification.className = 'global-notification';
      document.body.appendChild(notification);
    }

    // Icon mapping
    const icons = {
      'success': '<i class="ph ph-check-circle"></i>',
      'error': '<i class="ph ph-warning-circle"></i>',
      'warning': '<i class="ph ph-warning"></i>',
      'info': '<i class="ph ph-info"></i>'
    };

    // Create notification content
    notification.innerHTML = `
      <div class="notification-content ${type}">
        <div class="notification-icon">${icons[type] || icons.info}</div>
        <div class="notification-text">${message}</div>
      </div>
    `;

    // Remove existing classes and trigger animation
    notification.classList.remove('show');
    
    // Trigger reflow to restart animation
    void notification.offsetHeight;
    
    // Show notification
    setTimeout(() => {
      notification.classList.add('show');
    }, 10);

    // Auto-hide after duration
    setTimeout(() => {
      notification.classList.remove('show');
    }, duration);
  };

  /**
   * Show alert modal as notification (for backwards compatibility)
   * @param {string} message - The message to display
   */
  window.showAlertModal = function(message) {
    showNotification(message, 'warning', 4000);
  };

  /**
   * Show global notification (alias for showNotification)
   * @param {string} message - The message to display
   * @param {string} type - Type: 'success', 'error', 'warning', or 'info'
   */
  window.showGlobalNotification = function(message, type = 'info') {
    showNotification(message, type, 3000);
  };

  /**
   * Legacy support for old notification system
   * Intercepts old showNotification(message, color, duration) calls
   */
  const originalShowNotification = window.showNotification;
  window.showNotification = function(message, typeOrColor, duration) {
    // If second parameter is a number, shift parameters (old duration-only call)
    if (typeof typeOrColor === 'number') {
      duration = typeOrColor;
      typeOrColor = 'info';
    }
    
    // Call the new notification system
    originalShowNotification(message, typeOrColor || 'info', duration || 3000);
  };

  console.log('✅ Unified Notification System loaded');
})();
