/* assets/js/progress.js */
/* به‌روزرسانی پویای نوار پیشرفت تولید */

document.addEventListener('DOMContentLoaded', function() {

  const progressTracker = document.querySelector('.progress-tracker');
  if (!progressTracker) return;

  // دریافت وضعیت فعلی از data-status (مقادیر: 0,1,2,3,4)
  const currentStatus = parseInt(progressTracker.dataset.status || '0');

  const steps = progressTracker.querySelectorAll('.progress-step');

  steps.forEach((step, index) => {
    if (index < currentStatus) {
      step.classList.add('completed');
    }
  });

  // انیمیشن خط پیشروی
  if (currentStatus > 0) {
    const lines = progressTracker.querySelectorAll('.progress-step::before');
    // با CSS انجام می‌شود
  }

  // دکمه‌های تغییر وضعیت (در صورت وجود)
  document.querySelectorAll('[data-update-status]').forEach(btn => {
    btn.addEventListener('click', function() {
      const newStatus = parseInt(this.dataset.updateStatus);
      if (confirm('آیا از تغییر وضعیت اطمینان دارید؟')) {
        // ارسال Ajax (این‌جا شبیه‌سازی شده)
        steps.forEach((step, index) => {
          if (index < newStatus) {
            step.classList.add('completed');
          } else {
            step.classList.remove('completed');
          }
        });
        showToast('وضعیت سفارش با موفقیت بروزرسانی شد', 'success');
      }
    });
  });

});