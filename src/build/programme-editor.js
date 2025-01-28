(()=>{"use strict";const e=window.wp.blocks,r=JSON.parse('{"apiVersion":2,"name":"osm/programme","title":"OSM Programme","category":"widgets","icon":"calendar","description":"Display the OSM programme for a section.","attributes":{"sectionid":{"type":"string","default":""},"futureonly":{"type":"boolean","default":false}},"editorScript":"build/programme-editor.js","editorStyle":"build/programme-editor.css","style":"build/programme-style.css","render":"file:./render.php"}'),t=window.React,o=window.wp.i18n,n=window.wp.blockEditor,s=window.wp.components;(0,e.registerBlockType)(r.name,{...r,edit:function({attributes:e,setAttributes:r}){const{sectionid:l,futureonly:i}=e;return(0,t.createElement)(t.Fragment,null,(0,t.createElement)(n.InspectorControls,null,(0,t.createElement)(s.PanelBody,{title:(0,o.__)("Programme Settings","osm-for-wordpress")},(0,t.createElement)(s.TextControl,{label:(0,o.__)("Section ID","osm-for-wordpress"),value:l,onChange:e=>r({sectionid:e})}),(0,t.createElement)(s.ToggleControl,{label:(0,o.__)("Future Only","osm-for-wordpress"),checked:i,onChange:e=>r({futureonly:e})}))),(0,t.createElement)("div",{...(0,n.useBlockProps)()},(0,t.createElement)("strong",null,(0,o.__)("OSM Programme","osm-for-wordpress")),(0,t.createElement)("p",null,(0,o.__)("Section ID:","osm-for-wordpress")," ",l||(0,o.__)("Not set","osm-for-wordpress")),(0,t.createElement)("p",null,(0,o.__)("Future Only:","osm-for-wordpress")," ",i?(0,o.__)("Yes","osm-for-wordpress"):(0,o.__)("No","osm-for-wordpress"))))},save:function({attributes:e}){const{sectionid:r,futureonly:o}=e;return(0,t.createElement)("div",{...(0,n.useBlockProps)()},(0,t.createElement)("strong",null,"OSM Programme"),(0,t.createElement)("p",null,"Section ID: ",r),(0,t.createElement)("p",null,"Future Only: ",o?"Yes":"No"))}})})();