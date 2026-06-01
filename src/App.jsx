import React from 'react'

export default function App({ mountId }) {
  return (
    <div style={{padding:12,background:'#111',color:'#fff',borderRadius:8,fontFamily:'inherit'}}>
      <strong>Jade Sole React</strong>
      <div style={{fontSize:13,opacity:0.85,marginTop:6}}>This small React widget is mounted to <code>{mountId || 'react-root'}</code>.</div>
    </div>
  )
}
