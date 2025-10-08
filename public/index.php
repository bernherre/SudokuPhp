<?php
// Front controller simple que sirve la UI. Las APIs están en api.php
?><!doctype html>
<html lang="es" data-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sudoku PHP (4x4 / 6x6 / 9x9)</title>
    <meta name="color-scheme" content="dark light">
    <link rel="icon" href="data:,">
    <style>
      :root{
        color-scheme: dark light;
        --bg:#0b0f14; --panel:#121821; --text:#e6edf3; --muted:#9fb1c1;
        --accent:#6aa9ff; --error:#ff6a6a; --ok:#6aff9c; --cell:#1a2330;
        --cell-alt:#1f2a3a; --cell-fixed:#263448; --cell-highlight:#243349; --border:#2a3a52;
      }
      html[data-theme="light"]{ --bg:#f6f8fa; --panel:#fff; --text:#0a0f14; --muted:#4a5a6b; --accent:#005fcc; --error:#c62828; --ok:#2e7d32; --cell:#fff; --cell-alt:#f2f6fa; --cell-fixed:#e9eef5; --cell-highlight:#dce8f8; --border:#d0d8e2; }
      *{box-sizing:border-box}
      body{margin:0;font-family:system-ui,Segoe UI,Roboto;background:var(--bg);color:var(--text);min-height:100dvh;display:grid;place-items:start center;padding:24px}
      header{display:flex;gap:12px;flex-wrap:wrap;align-items:center;justify-content:space-between;background:var(--panel);padding:12px 16px;border:1px solid var(--border);border-radius:14px;margin-bottom:16px;box-shadow:0 6px 24px rgba(0,0,0,.2)}
      .controls{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
      select,button{appearance:none;border:1px solid var(--border);background:var(--panel);color:var(--text);padding:8px 12px;border-radius:10px;cursor:pointer;font-size:14px}
      button:hover{filter:brightness(1.08)}
      .timer,.status{font-variant-numeric:tabular-nums;color:var(--muted)}
      .board{display:grid;gap:2px;background:var(--border);border:2px solid var(--border);border-radius:12px;overflow:hidden;margin-top:12px}
      .cell{background:var(--cell);width:42px;height:42px;display:grid;place-items:center;font-weight:600;border:1px solid var(--border)}
      .cell:nth-child(odd){background:var(--cell-alt)}
      .cell.fixed{background:var(--cell-fixed);color:var(--muted)}
      .cell.error{outline:2px solid var(--error)}
      .cell.highlight{background:var(--cell-highlight)}
      .footer-info{margin-top:12px;color:var(--muted);font-size:14px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
      a{color:var(--accent)} kbd{background:var(--cell);border:1px solid var(--border);border-radius:6px;padding:2px 6px}
    </style>
  </head>
  <body>
    <div id="app"></div>
    <script>
      const $ = (s,ctx=document)=>ctx.querySelector(s);
      const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
      const API = (action, body) => fetch('api.php?action='+action, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: body ? JSON.stringify(body) : null
      }).then(r=>r.json());

      const formatTime = (sec)=>{
        const h=Math.floor(sec/3600), m=Math.floor((sec%3600)/60), s=sec%60;
        return (h>0? h+':' : '') + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
      };

      class App{
        constructor(root){
          this.root=root;
          this.state={ size:9, difficulty:'easy', puzzle:null, solution:null, current:null, fixed:null, selected:null, startTs:Date.now(), elapsedSec:0, running:true, dark:true };
          document.documentElement.setAttribute('data-theme', this.state.dark ? 'dark':'light');
          this.render();
          this.newPuzzle();
          this.timer = setInterval(()=>{
            if(this.state.running){
              this.state.elapsedSec = Math.floor((Date.now()-this.state.startTs)/1000);
              const t = $('.timer'); if(t) t.textContent = '⏱️ '+formatTime(this.state.elapsedSec);
            }
          },1000);
          document.addEventListener('keydown', (e)=>{
            if(!this.state.selected) return;
            const [r,c] = this.state.selected;
            if(this.state.fixed[r][c]) return;
            if(e.key==='Backspace' || e.key==='Delete'){ e.preventDefault(); this.state.current[r][c]=0; this.renderBoard(); return; }
            const n=parseInt(e.key,10);
            if(!Number.isNaN(n) && n>=0 && n<=this.state.size){
              e.preventDefault();
              if(n===0) this.state.current[r][c]=0; else this.state.current[r][c]=n;
              this.renderBoard();
            }
          });
        }

        async newPuzzle(){
          const data = await API('new', { size:this.state.size, difficulty:this.state.difficulty });
          this.state.puzzle=data.puzzle; this.state.solution=data.solution;
          this.state.current=JSON.parse(JSON.stringify(data.puzzle));
          this.state.fixed=this.state.puzzle.map(row=>row.map(v=>v!==0));
          this.state.startTs=Date.now(); this.state.elapsedSec=0; this.state.running=true;
          this.renderBoard();
          $('.status').textContent='Listo.';
        }

        async hint(){
          // primera vacía desde el cliente
          for(let r=0;r<this.state.size;r++) for(let c=0;c<this.state.size;c++){
            if(this.state.current[r][c]===0){ this.state.current[r][c]=this.state.solution[r][c]; this.renderBoard(); return; }
          }
        }

        async check(){
          const res = await API('check', { current:this.state.current, solution:this.state.solution });
          $$('.cell').forEach(el=>el.classList.remove('error'));
          for(const [r,c] of res.errors){
            const idx = r*this.state.size + c;
            $$('.cell')[idx].classList.add('error');
          }
          if(res.ok){ $('.status').textContent='✔️ ¡Completado!'; this.state.running=false; }
          else if(res.errors.length===0){ $('.status').textContent='ℹ️ Sin errores (aún no completo).'; }
          else { $('.status').textContent=`❌ ${res.errors.length} error(es).`; }
        }

        async solveNow(){
          this.state.current = JSON.parse(JSON.stringify(this.state.solution));
          this.state.running=false;
          this.renderBoard();
          $('.status').textContent='🧠 Resuelto automáticamente.';
        }

        toggleTheme(btn){
          this.state.dark = !this.state.dark;
          document.documentElement.setAttribute('data-theme', this.state.dark?'dark':'light');
          btn.textContent = this.state.dark ? '🌙 Oscuro' : '🌞 Claro';
        }

        renderControls(){
          const header = document.createElement('header');
          const left = document.createElement('div'); left.className='controls';
          const sizeSel = document.createElement('select');
          sizeSel.innerHTML='<option value="4">4x4</option><option value="6">6x6</option><option value="9">9x9</option>';
          sizeSel.value=String(this.state.size);
          sizeSel.onchange=()=>{ this.state.size=parseInt(sizeSel.value,10); this.newPuzzle(); };

          const diffSel=document.createElement('select');
          diffSel.innerHTML='<option value="easy">Fácil</option><option value="medium">Media</option><option value="hard">Difícil</option>';
          diffSel.value=this.state.difficulty;
          diffSel.onchange=()=>{ this.state.difficulty=diffSel.value; this.newPuzzle(); };

          const newBtn=document.createElement('button'); newBtn.textContent='🆕 Nuevo'; newBtn.onclick=()=>this.newPuzzle();
          const hintBtn=document.createElement('button'); hintBtn.textContent='💡 Pista'; hintBtn.onclick=()=>this.hint();
          const checkBtn=document.createElement('button'); checkBtn.textContent='✔️ Check'; checkBtn.onclick=()=>this.check();
          const solveBtn=document.createElement('button'); solveBtn.textContent='🧩 Solución'; solveBtn.onclick=()=>this.solveNow();
          const themeBtn=document.createElement('button'); themeBtn.textContent=this.state.dark?'🌙 Oscuro':'🌞 Claro'; themeBtn.onclick=()=>this.toggleTheme(themeBtn);

          left.append(sizeSel,diffSel,newBtn,hintBtn,checkBtn,solveBtn,themeBtn);

          const right=document.createElement('div'); right.className='controls';
          const timer=document.createElement('div'); timer.className='timer'; timer.textContent='⏱️ 00:00';
          const status=document.createElement('div'); status.className='status'; status.textContent='Listo.';
          right.append(timer,status);

          header.append(left,right);
          return header;
        }

        renderBoard(){
          const host = $('.board-host');
          host.innerHTML='';
          const n=this.state.size;
          const board=document.createElement('div'); board.className='board';
          board.style.gridTemplateColumns=`repeat(${n},42px)`; board.style.gridTemplateRows=`repeat(${n},42px)`;

          for(let r=0;r<n;r++) for(let c=0;c<n;c++){
            const cell=document.createElement('div'); cell.className='cell';
            if(this.state.fixed[r][c] && this.state.puzzle[r][c]!==0) cell.classList.add('fixed');
            const v=this.state.current[r][c];
            cell.textContent=v? String(v):'';
            cell.onclick=()=>{
              this.state.selected=[r,c];
              $$('.cell').forEach(el=>el.classList.remove('highlight'));
              cell.classList.add('highlight');
            };
            board.appendChild(cell);
          }

          host.append(board);
        }

        render(){
          this.root.innerHTML='';
          this.root.appendChild(this.renderControls());
          const boardHost=document.createElement('div'); boardHost.className='board-host'; this.root.appendChild(boardHost);
          const footer=document.createElement('div'); footer.className='footer-info';
          footer.innerHTML='<span>Usa <kbd>1..n</kbd> y <kbd>Backspace</kbd>.</span><span>Subcuadros 2x2 / 2x3 / 3x3 según tamaño.</span>';
          this.root.appendChild(footer);
        }
      }

      new App(document.getElementById('app'));
    </script>
  </body>
</html>