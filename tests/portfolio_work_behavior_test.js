'use strict';
const assert = require('node:assert/strict');
const {filterProjects, centeredShowcaseOffset, initialShowcaseIndex} = require('../assets/js/portfolio-work.js');
function button(name) {
  const attrs = {'data-iw-filter':name,'aria-pressed':'false'};
  return {
    getAttribute:(key)=>attrs[key],
    setAttribute:(key,val)=>{attrs[key]=val;},
    classList:{toggle:()=>{}},
    attrs,
  };
}
function project(tags) {
  const attrs={'data-iw-work-categories':tags};
  return {getAttribute:(key)=>attrs[key],hidden:false};
}
const projects=[
  project('formation hosting nonprofit'),
  project('formation web travel'),
  project('web hosting healthcare'),
  project('web healthcare'),
  project('web hosting healthcare')
];
const buttons=['all','formation','web','hosting','healthcare','travel','nonprofit'].map(button);
const summary={textContent:''};
const empty={hidden:true};
const root={
  querySelectorAll:(name)=>name==='[data-iw-work-project]'?projects:buttons,
  querySelector:(name)=>name==='[data-iw-work-count]'?summary:empty,
};
assert.equal(filterProjects(root,'all'),5);
assert.equal(summary.textContent,'5 case studies');
assert.equal(filterProjects(root,'healthcare'),3);
assert.equal(projects.filter(p=>!p.hidden).length,3);
assert.equal(buttons[4].attrs['aria-pressed'],'true');
assert.equal(buttons[0].attrs['aria-pressed'],'false');
assert.equal(filterProjects(root,'formation'),2);
assert.equal(filterProjects(root,'travel'),1);
assert.equal(summary.textContent,'1 case study');
assert.equal(filterProjects(root,'nonprofit'),1);
assert.equal(filterProjects(root,'invalid-filter'),0);
assert.equal(empty.hidden,false);
assert.equal(filterProjects(root,'all'),5);
assert.equal(empty.hidden,true);
// Centered focus layout: three+ stories open on story two to show both peeks.
assert.equal(initialShowcaseIndex(0),0);
assert.equal(initialShowcaseIndex(1),0);
assert.equal(initialShowcaseIndex(2),0);
assert.equal(initialShowcaseIndex(6),1);

// Offsets are computed from visible rect centers, not legacy offsetLeft,
// so scaling adjacent cards does not break centering and either edge clamps.
const track = {
  scrollLeft:0,
  getBoundingClientRect:()=>({left:100,width:1000})
};
const first = {getBoundingClientRect:()=>({left:300,width:600})};
const second = {getBoundingClientRect:()=>({left:1070,width:600})};
assert.equal(centeredShowcaseOffset(track, first, 2200), 0);
assert.equal(centeredShowcaseOffset(track, second, 2200), 770);
assert.equal(centeredShowcaseOffset(track, second, 550), 550);
track.scrollLeft = 770;
const settled = {getBoundingClientRect:()=>({left:300,width:600})};
assert.equal(centeredShowcaseOffset(track, settled, 2200), 770);
assert.equal(centeredShowcaseOffset(track, settled, 0), 0);

console.log('portfolio_work_behavior_test passed');
