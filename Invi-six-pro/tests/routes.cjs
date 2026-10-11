const assert=require('node:assert/strict');
const http=require('node:http');
const origin=process.argv[2]||'http://127.0.0.1:8088';
const request=route=>new Promise((resolve,reject)=>{
 http.get(origin+route,{agent:false},response=>{
  let body='';response.setEncoding('utf8');response.on('data',chunk=>body+=chunk);
  response.on('end',()=>resolve({status:response.statusCode,headers:response.headers,body}));
 }).on('error',reject);
});
(async()=>{
 let checks=0;
 for(const route of ['/.env','/src/config/database.php','/database/schema.sql','/api/index.php','/public/README.md','/node_modules/package.json','/missing','/assets/missing.js','/assets/icons/fontawesome/LICENSE.txt','/%2eenv']){
  const response=await request(route);
  assert.equal(response.status,404,route);
  const text=response.body;
  assert.ok(!text.includes('<?php')&&!text.includes('Stack trace'),route);
  checks+=2;
 }
 for(const [route,type] of [['/assets/css/tailwind.css','text/css'],['/assets/images/logo-invisix.svg','image/svg+xml'],['/assets/js/match.js','javascript']]){
  const response=await request(route);
  assert.equal(response.status,200,route);
  assert.ok(response.headers['content-type'].includes(type),route);
  assert.equal(response.headers['cache-control'],'public, max-age=3600, s-maxage=86400',route);
  checks+=3;
 }
 const response=await request('/missing');
 assert.ok(response.headers['content-security-policy'].includes("frame-ancestors 'none'"));
 assert.equal(response.headers['x-content-type-options'],'nosniff');
 assert.equal(response.headers['cache-control'],'no-store');
 console.log(`${checks+3} HTTP checks passed. No authentication or database mutation endpoints called.`);
})().catch(error=>{console.error(error.message);process.exitCode=1;});
