const fs=require('node:fs'),path=require('node:path');
const files=d=>fs.readdirSync(d,{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(path.join(d,e.name)):[path.join(d,e.name)]);
const errors=[];let checks=0;
// Logos use a PHP catalog, so literal HTML URL checks do not see them.
const teamSource=fs.readFileSync('src/components/pro-teams.php','utf8');
const teamLogos=[...teamSource.matchAll(/'logo'=>'([^']+)'/g)].map(match=>match[1]);
if(teamLogos.length!==9||new Set(teamLogos).size!==9)errors.push('Expected nine distinct professional team logos');
for(const logo of teamLogos){
 const file=path.join('public/assets/images/teams',logo);checks++;
 if(!fs.existsSync(file)){errors.push(`Missing team logo: ${file}`);continue;}
 const data=fs.readFileSync(file);
 if(data.length<33||data.subarray(0,8).toString('hex')!=='89504e470d0a1a0a')errors.push(`Invalid PNG logo: ${file}`);
 else if(data.readUInt32BE(16)>128||data.readUInt32BE(20)>128)errors.push(`Unoptimized team logo: ${file}`);
}
// Check build inputs so a clean checkout cannot depend on local-only files.
const cssEntry=fs.readFileSync('public/assets/css/input.css','utf8');
for(const match of cssEntry.matchAll(/@(import|source)\s+"(\.[^";]+)"/g)){
 checks++;if(!fs.existsSync(path.resolve('public/assets/css',match[2])))errors.push('Missing CSS input: '+match[2]);
}
for(const file of files('src').filter(f=>f.endsWith('.php'))){
 const source=fs.readFileSync(file,'utf8');
 for(const field of source.matchAll(/<fieldset\b[^>]*class="ui-field[^>]*>([\s\S]*?)<\/fieldset>/g)){
  checks++;if(!/<legend>\s*<label\b/.test(field[1]))errors.push(file+': missing legend label');
 }
 if(/<div\b[^>]*class="ui-field[^>]*>\s*<label\b/.test(source))errors.push(file+': legacy floating label');
 for(const match of source.matchAll(/(?:src|href)=["'](\/assets\/[^"'<>?]+)["']|assetUrl\(['"](\/assets\/[^'"?]+)['"]\)/g)){
  match[1] ||= match[2];
  if(match[1].includes('<?'))continue;
  checks++;if(!fs.existsSync('public'+match[1]))errors.push(`${file}: ${match[1]}`);
 }
 for(const match of source.matchAll(/require(?:_once)?\s*\(?\s*__DIR__\s*\.\s*['"]([^'"]+)['"]/g)){
  checks++;if(!fs.existsSync(path.resolve(path.dirname(file),'.'+match[1])))errors.push(`${file}: require ${match[1]}`);
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
 if(!fs.existsSync(file))errors.push(`Missing PHP icon: ${file}`);
 else if(!/<svg\b[\s\S]*<\/svg>/.test(fs.readFileSync(file,'utf8')))errors.push(`Invalid PHP icon: ${file}`);
}
if(errors.length){console.error(errors.join('\n'));process.exitCode=1;}
else console.log(`${checks} static asset references and PHP runtime configuration passed.`);
