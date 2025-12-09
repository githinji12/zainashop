// main.js – Utility scripts
const BASE_URL = window.BASE_URL || location.origin + '/zainas-beauty-system/';
const CURRENCY = '<?php echo getSetting("currency","TZS"); ?>';
const TAX_RATE = '<?php echo getSetting("tax_rate",0); ?>';

// Flash auto-dismiss
document.addEventListener('DOMContentLoaded', ()=>{
  setTimeout(()=>{
    document.querySelectorAll('.alert').forEach(el=>{
      el.classList.remove('show');
    });
  }, 4000);
});