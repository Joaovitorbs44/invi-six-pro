const fs=require('node:fs'),path=require('node:path');
const files=d=>fs.readdirSync(d,{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(path.join(d,e.name)):[path.join(d,e.name)]);
const errors=[];let checks=0;
// Windows accepts wrong casing; Vercel/Linux does not. Check each path segment.
function existsWithExactCase(file){
 const absolute=path.resolve(file),root=path.parse(absolute).root;
 let current=root;
 for(const segment of absolute.slice(root.length).split(path.sep).filter(Boolean)){
  if(!fs.existsSync(current)||!fs.statSync(current).isDirectory()||!fs.readdirSync(current).includes(segment))return false;
  current=path.join(current,segment);
 }
 return fs.existsSync(current);
}
// These checks exercise case-sensitive validation even on a Windows checkout.
for(const [file,expected] of [['src/app/support.php',true],['src/app/SUPPORT.php',false],['src/App/support.php',false]]){
 checks++;if(existsWithExactCase(file)!==expected)errors.push(`Case-sensitive path validation failed: ${file}`);
}
// Logos use a PHP catalog, so literal HTML URL checks do not see them.
const teamSource=fs.readFileSync('src/components/pro-teams.php','utf8');
const teamLogos=[...teamSource.matchAll(/'logo'=>'([^']+)'/g)].map(match=>match[1]);
if(teamLogos.length!==9||new Set(teamLogos).size!==9)errors.push('Expected nine distinct professional team logos');
for(const logo of teamLogos){
 const file=path.join('public/assets/images/teams',logo);checks++;
 if(!existsWithExactCase(file)){errors.push(`Missing team logo or incorrect casing: ${file}`);continue;}
 const data=fs.readFileSync(file);
 if(data.length<33||data.subarray(0,8).toString('hex')!=='89504e470d0a1a0a')errors.push(`Invalid PNG logo: ${file}`);
 else if(data.readUInt32BE(16)>128||data.readUInt32BE(20)>128)errors.push(`Unoptimized team logo: ${file}`);
}
// Check build inputs so a clean checkout cannot depend on local-only files.
const cssEntry=fs.readFileSync('public/assets/css/input.css','utf8');
for(const match of cssEntry.matchAll(/@(import|source)\s+"(\.[^";]+)"/g)){
 checks++;if(!existsWithExactCase(path.resolve('public/assets/css',match[2])))errors.push('Missing CSS input or incorrect casing: '+match[2]);
}
for(const file of files('src').filter(f=>f.endsWith('.php'))){
 const source=fs.readFileSync(file,'utf8');
 // Check label/control associations across templates without executing PHP or
 // touching authentication/database endpoints. Hidden/checkbox inputs keep
 // their existing semantics and are not outlined text controls.
 const html=source.replace(/<\?(?:php|=)[\s\S]*?\?>/g,''),fieldsets=[];
 const attribute=(attrs,name)=>attrs.match(new RegExp(`\\b${name}=(['"])([^'"]*)\\1`))?.[2];
 for(const tag of html.matchAll(/<(\/?)(fieldset|legend|label|input|select|textarea)\b([^>]*)>/g)){
  const [,closing,name,attrs]=tag;
  if(name==='fieldset'){
   if(!closing)fieldsets.push({outlined:(attribute(attrs,'class')||'').split(/\s+/).includes('ui-field-outline'),legend:false,labels:[],controls:0});
   else {const field=fieldsets.pop();if(field?.outlined&&field.controls!==1)errors.push(`${file}: outlined field must have one control`);}
   continue;
  }
  const field=fieldsets.at(-1);
  if(name==='legend'){if(field)field.legend=!closing;continue;}
  if(closing)continue;
  if(name==='label'){if(field?.legend)field.labels.push(attribute(attrs,'for'));continue;}
  if(name==='input'&&['hidden','checkbox','radio','submit','button'].includes(attribute(attrs,'type')))continue;
  checks++;
  const id=attribute(attrs,'id');
  if(!field?.outlined||!id||!field.labels.includes(id))errors.push(`${file}: ${name} ${id||'(no id)'} needs an outlined legend label`);
  if(field)field.controls++;
 }
 for(const field of source.matchAll(/<fieldset\b[^>]*class="ui-field[^>]*>([\s\S]*?)<\/fieldset>/g)){
  checks++;if(!/<legend>\s*<label\b/.test(field[1]))errors.push(file+': missing legend label');
 }
 if(/<div\b[^>]*class="ui-field[^>]*>\s*<label\b/.test(source))errors.push(file+': legacy floating label');
 for(const match of source.matchAll(/(?:src|href)=["'](\/assets\/[^"'<>?]+)["']|assetUrl\(['"](\/assets\/[^'"?]+)['"]\)/g)){
  match[1] ||= match[2];
  if(match[1].includes('<?'))continue;
  checks++;if(!existsWithExactCase('public'+match[1]))errors.push(`${file}: missing asset or incorrect casing ${match[1]}`);
 }
 for(const match of source.matchAll(/\brequire(?:_once)?\s*\(?\s*__DIR__\s*\.\s*(['"])([^'"]+)\1/g)){
  const relative=match[2];checks++;
  if(relative.includes('\\'))errors.push(`${file}: require ${relative} (use forward slashes for Linux)`);
  else if(!existsWithExactCase(path.resolve(path.dirname(file),'.'+relative)))errors.push(`${file}: require ${relative} (missing file or incorrect casing)`);
 }
}
const config=JSON.parse(fs.readFileSync('vercel.json','utf8'));
if(!config.functions?.['api/index.php']?.runtime)errors.push('Missing PHP runtime');
// Icons are loaded dynamically by PHP, so literal HTML asset checks miss them.
const iconSource=fs.readFileSync('src/components/icons.php','utf8');
const allowedIcons=iconSource.match(/\$allowed\s*=\s*\[([\s\S]*?)\];/);
if(!allowedIcons)errors.push('Missing icon catalog');
else for(const match of allowedIcons[1].matchAll(/'([^']+)'/g)){
 const file=`public/assets/icons/fontawesome/${match[1]}.svg`;
 checks++;
 if(!existsWithExactCase(file))errors.push(`Missing PHP icon or incorrect casing: ${file}`);
 else if(!/<svg\b[\s\S]*<\/svg>/.test(fs.readFileSync(file,'utf8')))errors.push(`Invalid PHP icon: ${file}`);
}
if(errors.length){console.error(errors.join('\n'));process.exitCode=1;}
else console.log(`${checks} static asset references and PHP runtime configuration passed.`);
