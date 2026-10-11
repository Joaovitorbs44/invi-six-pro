const fs=require('node:fs'),path=require('node:path');
const files=d=>fs.readdirSync(d,{withFileTypes:true}).flatMap(e=>e.isDirectory()?files(path.join(d,e.name)):[path.join(d,e.name)]);
const errors=[];let checks=0;
for(const file of files('src').filter(f=>f.endsWith('.php'))){
 const source=fs.readFileSync(file,'utf8');
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
