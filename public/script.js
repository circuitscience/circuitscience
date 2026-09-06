const toggle=document.querySelector('.menu-toggle');
const nav=document.querySelector('#primary-nav');
toggle?.addEventListener('click',()=>{const open=toggle.getAttribute('aria-expanded')==='true';toggle.setAttribute('aria-expanded',String(!open));nav.classList.toggle('open',!open)});
nav?.querySelectorAll('a').forEach(link=>link.addEventListener('click',()=>{nav.classList.remove('open');toggle?.setAttribute('aria-expanded','false')}));

const form=document.querySelector('#estimate-form');
form?.addEventListener('submit',()=>{
  const button=form.querySelector('button[type="submit"]');
  button.disabled=true;
  button.textContent='Sending…';
  document.querySelector('.form-status').textContent='Please wait while your request is sent.';
});
