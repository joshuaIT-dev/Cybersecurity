(async()=>{
 if(current!=='security')return;
 let state;
 const node=(tag,text,cls)=>{const el=document.createElement(tag);if(text)el.textContent=text;if(cls)el.className=cls;return el;};
 const emailForm=document.getElementById('student-email-form');
 let emailSummary,verifyForm,pendingText,emailStatus,sessionList,sessionStatus,sessionPassword;
 async function load(){const response=await fetch('api.php');const result=await response.json();if(!response.ok)throw Error(result.error||'Sign in again.');state=result;render();}
 async function submit(kind,form,status,extra={}){
  const buttons=[...form.querySelectorAll('button')];buttons.forEach(b=>b.disabled=true);status.hidden=false;status.textContent='Saving…';
  try{
   const data=new FormData(form);data.set('kind',kind);data.set('csrf',state.csrf);for(const [key,value]of Object.entries(extra))data.set(key,value);
   const response=await fetch('api.php',{method:'POST',body:data});const result=await response.json();if(!response.ok)throw Error(result.error||'Unable to save.');
   form.querySelectorAll('input[type=password]').forEach(input=>input.value='');state.security=result.security;render();status.textContent=result.message;
  }catch(error){if(kind==='verify_email'){try{await load();}catch{}}status.textContent=error.message;}finally{buttons.forEach(b=>b.disabled=false);}
 }
 if(current==='security'){
  const panel=node('section',null,'security-card session-review');panel.append(node('h2','Your signed-in sessions'),node('p','Review your browsers and sign out any session you do not recognize. Sessions expire after 12 hours. Times are Philippine time.'));
  sessionList=node('div',null,'session-list');sessionStatus=node('p');sessionStatus.setAttribute('role','status');
  const form=node('form');const label=node('label','Current password to sign out another session');sessionPassword=node('input');sessionPassword.type='password';sessionPassword.name='current_password';sessionPassword.autocomplete='current-password';sessionPassword.required=true;label.append(sessionPassword);form.append(label,sessionList,sessionStatus);panel.append(form);document.querySelector('.profile-tool-page').append(panel);
  form.addEventListener('submit',event=>event.preventDefault());
  sessionList.addEventListener('click',event=>{const button=event.target.closest('[data-session]');if(button&&form.reportValidity())submit('revoke_session',form,sessionStatus,{session_id:button.dataset.session});});
 }
 function render(){
  const security=state.security;
  if(sessionList){sessionList.replaceChildren();for(const session of security.sessions){
   const row=node('article',null,'session-item');row.append(node('strong',session.current?'This session':'Other session'),node('p',session.user_agent||'Unknown browser'),node('small','IP '+session.source_ip+' · Last active '+session.last_seen+' PHT'));
   if(!session.current){const button=node('button','Sign out session');button.type='button';button.dataset.session=session.id;row.append(button);}sessionList.append(row);
  }}
 }
 try{await load();}catch(error){const status=emailStatus||sessionStatus;status.hidden=false;status.textContent=error.message;}
 document.addEventListener('account-security-updated',()=>load().catch(error=>{if(sessionStatus)sessionStatus.textContent=error.message;}));
})();
