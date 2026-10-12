'use strict';
// Local release gate. No credentials, environment changes, Git or deployment calls.
const fs=require('node:fs'),path=require('node:path'),http=require('node:http'),net=require('node:net');
const {spawn,spawnSync}=require('node:child_process');
const root=path.resolve(__dirname,'..');
process.chdir(root);
const files=dir=>fs.readdirSync(dir,{withFileTypes:true}).flatMap(entry=>entry.isDirectory()?files(path.join(dir,entry.name)):[path.join(dir,entry.name)]);
function run(command,args){
 const result=spawnSync(command,args,{cwd:root,stdio:'inherit',windowsHide:true});
 if(result.error)throw result.error;
 if(result.status!==0)throw new Error(`${command} ${args.join(' ')} failed (${result.status}). Publishing is blocked.`);
}
function lint(){
 const php=[...['src','scripts','tests','api'].flatMap(files).filter(file=>file.endsWith('.php')),'router.php'];
 for(const file of php){
  const result=spawnSync('php',['-l',file],{cwd:root,encoding:'utf8',windowsHide:true});
  if(result.error)throw result.error;
  if(result.status!==0)throw new Error(`PHP lint failed: ${file}\n${result.stdout}${result.stderr}`);
 }
 const js=['public/assets/js','tests','scripts'].flatMap(files).filter(file=>/\.(?:js|cjs)$/.test(file));
 for(const file of js)run(process.execPath,['--check',file]);
 console.log(`Lint passed: ${php.length} PHP files and ${js.length} JavaScript files.`);
}
const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
function status(url){return new Promise((resolve,reject)=>{
 const request=http.get(url,{agent:false},response=>{response.resume();response.on('end',()=>resolve(response.statusCode));});
 request.on('error',reject);request.setTimeout(1000,()=>request.destroy(new Error('HTTP readiness timeout')));
});}
async function tests(){
 run('php',['tests/check.php']);run('php',['tests/csrf-reject.php']);
 const port=await new Promise((resolve,reject)=>{
  const reservation=net.createServer();reservation.once('error',reject);
  reservation.listen(0,'127.0.0.1',()=>{const port=reservation.address().port;reservation.close(()=>resolve(port));});
 });
 const server=spawn('php',['-S',`127.0.0.1:${port}`,'-t','public','router.php'],{cwd:root,stdio:'ignore',windowsHide:true});
 let serverError;server.on('error',error=>{serverError=error;});
 try{
  const origin=`http://127.0.0.1:${port}`;let ready=false;
  for(let attempt=0;attempt<50;attempt++){
   if(serverError)throw serverError;
   if(server.exitCode!==null)throw new Error('PHP test server exited before readiness.');
   try{if(await status(origin+'/missing')===404){ready=true;break;}}catch{}
   await delay(100);
  }
  if(!ready)throw new Error('PHP test server did not become ready.');
  const routeArgs=['tests/routes.cjs',origin];
  if(process.env.VERCEL!=='1')routeArgs.push('--php-development');
  run(process.execPath,routeArgs);
 }finally{
  if(server.exitCode===null&&!serverError){
   const closed=new Promise(resolve=>server.once('exit',resolve));server.kill();await closed;
  }
 }
}
async function main(){
 const mode=process.argv[2];
 if(mode&&!['lint','test'].includes(mode))throw new Error('Usage: node scripts/verify.cjs [lint|test]');
 if(!mode||mode==='lint')lint();
 if(!mode||mode==='test')await tests();
 if(!mode){
  if(!process.env.npm_execpath)throw new Error('Run the release gate with npm run verify.');
  run(process.execPath,[process.env.npm_execpath,'run','build']);
 }
 console.log('Requested checks passed. Nothing was committed or published.');
}
main().catch(error=>{console.error(error.message);process.exitCode=1;});
