</main>
<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> TechCenter</p>
</footer>
<script>
  function toast(msg){
    const t=document.createElement('div');
    t.className='toast'; t.textContent=msg; document.body.appendChild(t);
    setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(8px)'; },1400);
    setTimeout(()=>t.remove(),2200);
  }
</script>
</body>
</html>
