/* Render de reportes contables fuera del hilo de la interfaz. */
'use strict';
// La versión incluida inicializa FileSaver al cargar; el worker solo usa getBuffer.
self.window = self;
self.document = { createElementNS: () => ({}) };
importScripts('../bootstrap/js/pdfmake.min.js', '../bootstrap/js/vfs_fonts.js');

const moneyFormatter = new Intl.NumberFormat('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatMoney = value => moneyFormatter.format(Number(value));

function ingresos({rows, logo, filters}) {
  function toNumber(val) {
    if (val == null) return 0;
    if (typeof val === "number") return val;

    return parseFloat(String(val).replace(/[^\d.-]/g, "")) || 0;
  }
    var subtotal=0,impuesto=0,descuento=0,total=0;rows.forEach(function(r){subtotal+=toNumber(r.subtotal);impuesto+=toNumber(r.impuesto);descuento+=toNumber(r.descuento);total+=toNumber(r.total);});
    var body=[[{text:'FECHA',style:'th'},{text:'TIPO',style:'th'},{text:'INGRESO #',style:'th'},{text:'FECHA FACT.',style:'th'},{text:'FORMA PAGO',style:'th'},{text:'RECIBÍ DE',style:'th'},{text:'FACTURA',style:'th'},{text:'SUBTOTAL',style:'th'},{text:'IMPUESTO',style:'th'},{text:'DESCUENTO',style:'th'},{text:'TOTAL',style:'th'}]];
    rows.forEach(function(r,i){var fill=i%2===0?'#FFFFFF':'#F7F9FC';body.push([{text:String(r.fecha_registro||''),style:'td',fillColor:fill},{text:String(r.tipo_ingreso||''),style:'td',fillColor:fill},{text:String(r.ingresos_id||''),style:'td',fillColor:fill},{text:String(r.fecha||''),style:'td',fillColor:fill},{text:String(r.nombre||''),style:'td',fillColor:fill},{text:String(r.cliente||''),style:'td',fillColor:fill},{text:String(r.factura||''),style:'td',fillColor:fill},{text:'L. '+formatMoney(toNumber(r.subtotal)),style:'tdn',fillColor:fill},{text:'L. '+formatMoney(toNumber(r.impuesto)),style:'tdn',fillColor:fill},{text:'L. '+formatMoney(toNumber(r.descuento)),style:'tdn',fillColor:fill},{text:'L. '+formatMoney(toNumber(r.total)),style:'tdn',fillColor:fill,bold:true,color:'#14804A'}]);});
    body.push([{text:'TOTALES',colSpan:7,style:'totalLabel',fillColor:'#EAF1F7'},{},{},{},{},{},{},{text:'L. '+formatMoney(subtotal),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L. '+formatMoney(impuesto),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L. '+formatMoney(descuento),style:'totalMoney',fillColor:'#EAF1F7'},{text:'L. '+formatMoney(total),style:'totalMoneyCurrent',fillColor:'#EAF1F7'}]);
    var logoCell=logo?{table:{widths:['*'],body:[[{image:logo,fit:[74,44],alignment:'center',margin:[7,5,7,5],fillColor:'#FFFFFF'}]]},layout:'noBorders',fillColor:'#17324D',margin:[8,7,8,7]}:{text:'IZZY',bold:true,fontSize:18,color:'#17324D',alignment:'center',fillColor:'#FFFFFF',margin:[8,14,8,14]};
    var doc={pageSize:'LEGAL',pageOrientation:'landscape',pageMargins:[28,28,28,34],header:function(){return{margin:[28,12,28,0],canvas:[{type:'line',x1:0,y1:0,x2:952,y2:0,lineWidth:2,lineColor:'#0EA5A8'}]};},footer:function(page,pages){return{margin:[28,8,28,0],columns:[{text:'IZZY • Reporte de Ingresos',fontSize:7,color:'#7A869A'},{text:'Página '+page+' de '+pages,fontSize:7,color:'#7A869A',alignment:'right'}]};},content:[
      {table:{widths:[110,'*',165],body:[[logoCell,{stack:[{text:'REPORTE DE INGRESOS',bold:true,fontSize:16,color:'#FFFFFF'},{text:'Registro contable de ingresos por período',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}],fillColor:'#17324D',margin:[0,10,0,10]},{stack:[{text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},{text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},{text:rows.length+' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}],fillColor:'#17324D',margin:[0,10,12,10]}]]},layout:'noBorders',margin:[0,0,0,10]},
      {table:{widths:['*'],body:[[{text:'Estado: '+filters.estado+'   |   Período: '+filters.desde+' a '+filters.hasta+'   |   Búsqueda: '+filters.busqueda,fontSize:7,color:'#52627A',fillColor:'#F7F9FC',margin:[8,6,8,6]}]]},layout:'lightHorizontalLines',margin:[0,0,0,10]},
      {table:{widths:['*','*','*','*'],body:[[{stack:[{text:'REGISTROS',fontSize:6.2,bold:true,color:'#6B778C'},{text:String(rows.length),fontSize:12,bold:true,color:'#17324D'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'SUBTOTAL',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L. '+formatMoney(subtotal),fontSize:11,bold:true,color:'#17324D'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'IMPUESTO',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L. '+formatMoney(impuesto),fontSize:11,bold:true,color:'#0EA5A8'}],fillColor:'#F7F9FC',margin:[8,7,8,7]},{stack:[{text:'TOTAL',fontSize:6.2,bold:true,color:'#6B778C'},{text:'L. '+formatMoney(total),fontSize:11,bold:true,color:'#14804A'}],fillColor:'#F7F9FC',margin:[8,7,8,7]}]]},layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return .5;},vLineWidth:function(){return .5;}},margin:[0,0,0,10]},
      {table:{headerRows:1,widths:[74,68,56,66,88,112,88,78,76,76,82],body:body},margin:[0,0,0,0],layout:{hLineColor:function(){return'#DDE3EA';},vLineColor:function(){return'#DDE3EA';},hLineWidth:function(){return .55;},vLineWidth:function(){return .55;},paddingLeft:function(){return 4;},paddingRight:function(){return 4;},paddingTop:function(){return 5;},paddingBottom:function(){return 5;}}}
    ],styles:{th:{fontSize:5.9,bold:true,color:'#FFFFFF',fillColor:'#17324D',alignment:'center'},td:{fontSize:6.1,color:'#253858',noWrap:false},tdn:{fontSize:6.1,color:'#253858',alignment:'right',noWrap:false},totalLabel:{fontSize:6.4,bold:true,color:'#17324D'},totalMoney:{fontSize:6.4,bold:true,color:'#17324D',alignment:'right'},totalMoneyCurrent:{fontSize:6.4,bold:true,color:'#14804A',alignment:'right'}}};
  return doc;
}

function egresosValor(value, fallback) {
  if (value === null || value === undefined || String(value).trim() === '') {
    return fallback === undefined ? 'No registrado' : fallback;
  }
  return String(value).trim();
}

function egresos({rows, logo, filters}) {
  function toNumber(val) {
    if (val == null || val === '') return 0;
    if (typeof val === 'number') return val;

    var normalized = String(val).replace(/,/g, '').trim();
    var match = normalized.match(/-?\d+(?:\.\d+)?/);

    return match ? (parseFloat(match[0]) || 0) : 0;
  }
  const reportMoney = value => 'L. ' + formatMoney(toNumber(value));
  var subtotal = 0, impuesto = 0, descuento = 0, nc = 0, total = 0;

  rows.forEach(function(r) {
    subtotal += toNumber(r.subtotal_raw != null ? r.subtotal_raw : r.subtotal);
    impuesto += toNumber(r.isv_raw != null ? r.isv_raw : r.impuesto);
    descuento += toNumber(r.descuento_raw != null ? r.descuento_raw : r.descuento);
    nc += toNumber(r.nc_raw != null ? r.nc_raw : r.nc);
    total += toNumber(r.total_raw != null ? r.total_raw : r.total);
  });

    var body = [[
      {text:'FECHA REG.',style:'th'},
      {text:'NÚM.',style:'th'},
      {text:'CATEGORÍA',style:'th'},
      {text:'FECHA FACT.',style:'th'},
      {text:'FORMA PAGO',style:'th'},
      {text:'PROVEEDOR',style:'th'},
      {text:'FACTURA',style:'th'},
      {text:'SUBTOTAL',style:'th'},
      {text:'IMPUESTO',style:'th'},
      {text:'DESCUENTO',style:'th'},
      {text:'NOTA CRÉD.',style:'th'},
      {text:'TOTAL',style:'th'}
    ]];

    rows.forEach(function(r, i) {
      var fill = i % 2 === 0 ? '#FFFFFF' : '#F7F9FC';

      body.push([
        {text:egresosValor(r.fecha_registro,''),style:'td',fillColor:fill},
        {text:String(r.egresos_id || ''),style:'td',fillColor:fill},
        {text:egresosValor(r.categoria,''),style:'td',fillColor:fill},
        {text:egresosValor(r.fecha,''),style:'td',fillColor:fill},
        {text:egresosValor(r.nombre,''),style:'td',fillColor:fill},
        {text:egresosValor(r.proveedor,''),style:'td',fillColor:fill},
        {text:egresosValor(r.factura,''),style:'td',fillColor:fill},
        {text:reportMoney(r.subtotal),style:'tdn',fillColor:fill},
        {text:reportMoney(r.impuesto),style:'tdn',fillColor:fill},
        {text:reportMoney(r.descuento),style:'tdn',fillColor:fill},
        {text:reportMoney(r.nc),style:'tdn',fillColor:fill},
        {text:reportMoney(r.total),style:'tdn',fillColor:fill,bold:true,color:'#C9372C'}
      ]);
    });

    body.push([
      {text:'TOTALES',colSpan:7,style:'totalLabel',fillColor:'#EAF1F7'},
      {},{},{},{},{},{},
      {text:'L. ' + formatMoney(subtotal),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L. ' + formatMoney(impuesto),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L. ' + formatMoney(descuento),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L. ' + formatMoney(nc),style:'totalMoney',fillColor:'#EAF1F7'},
      {text:'L. ' + formatMoney(total),style:'totalMoneyDanger',fillColor:'#EAF1F7'}
    ]);

    var logoCell = logo
      ? {
          table:{
            widths:['*'],
            body:[[
              {
                image:logo,
                fit:[74,44],
                alignment:'center',
                margin:[7,5,7,5],
                fillColor:'#FFFFFF'
              }
            ]]
          },
          layout:'noBorders',
          fillColor:'#17324D',
          margin:[8,7,8,7]
        }
      : {
          text:'IZZY',
          bold:true,
          fontSize:18,
          color:'#17324D',
          alignment:'center',
          fillColor:'#FFFFFF',
          margin:[8,14,8,14]
        };

    const {estado: estadoTexto, desde, hasta, busqueda} = filters;

    var doc = {
      pageSize:'LEGAL',
      pageOrientation:'landscape',
      pageMargins:[28,28,28,34],

      header:function() {
        return {
          margin:[28,12,28,0],
          canvas:[{
            type:'line',
            x1:0,y1:0,x2:952,y2:0,
            lineWidth:2,
            lineColor:'#0EA5A8'
          }]
        };
      },

      footer:function(page,pages) {
        return {
          margin:[28,8,28,0],
          columns:[
            {text:'IZZY • Reporte de Egresos',fontSize:7,color:'#7A869A'},
            {text:'Página ' + page + ' de ' + pages,fontSize:7,color:'#7A869A',alignment:'right'}
          ]
        };
      },

      content:[
        {
          table:{
            widths:[110,'*',175],
            body:[[
              logoCell,
              {
                stack:[
                  {text:'REPORTE DE EGRESOS',bold:true,fontSize:16,color:'#FFFFFF'},
                  {text:'Registro contable de egresos por período',fontSize:8,color:'#D8E5F0',margin:[0,2,0,0]}
                ],
                fillColor:'#17324D',
                margin:[0,10,0,10]
              },
              {
                stack:[
                  {text:'REPORTE EJECUTIVO',bold:true,fontSize:6.5,color:'#72E2E5',alignment:'right'},
                  {text:new Date().toLocaleDateString('es-HN'),bold:true,fontSize:9,color:'#FFFFFF',alignment:'right'},
                  {text:rows.length + ' registro(s)',fontSize:6.5,color:'#D8E5F0',alignment:'right'}
                ],
                fillColor:'#17324D',
                margin:[0,10,12,10]
              }
            ]]
          },
          layout:'noBorders',
          margin:[0,0,0,10]
        },

        {
          table:{
            widths:['*'],
            body:[[
              {
                text:'Estado: ' + estadoTexto +
                     '   |   Período: ' + desde + ' a ' + hasta +
                     '   |   Búsqueda: ' + busqueda,
                fontSize:7,
                color:'#52627A',
                fillColor:'#F7F9FC',
                margin:[8,6,8,6]
              }
            ]]
          },
          layout:'lightHorizontalLines',
          margin:[0,0,0,10]
        },

        {
          table:{
            widths:['*','*','*','*'],
            body:[[
              {
                stack:[
                  {text:'REGISTROS',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:String(rows.length),fontSize:12,bold:true,color:'#17324D',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'SUBTOTAL',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L. ' + formatMoney(subtotal),fontSize:11,bold:true,color:'#17324D',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'IMPUESTO',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L. ' + formatMoney(impuesto),fontSize:11,bold:true,color:'#5949BA',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              },
              {
                stack:[
                  {text:'TOTAL EGRESOS',fontSize:6.2,bold:true,color:'#6B778C'},
                  {text:'L. ' + formatMoney(total),fontSize:11,bold:true,color:'#C9372C',margin:[0,2,0,0]}
                ],
                fillColor:'#F7F9FC',margin:[8,7,8,7]
              }
            ]]
          },
          layout:{
            hLineColor:function(){return '#DDE3EA';},
            vLineColor:function(){return '#DDE3EA';},
            hLineWidth:function(){return .5;},
            vLineWidth:function(){return .5;}
          },
          margin:[0,0,0,10]
        },

        {
          table:{
            headerRows:1,
            widths:[78,44,72,58,86,104,92,68,64,64,64,62],
            body:body
          },
          margin:[0,0,0,0],
          layout:{
            hLineColor:function(){return '#DDE3EA';},
            vLineColor:function(){return '#DDE3EA';},
            hLineWidth:function(){return .55;},
            vLineWidth:function(){return .55;},
            paddingLeft:function(){return 4;},
            paddingRight:function(){return 4;},
            paddingTop:function(){return 5;},
            paddingBottom:function(){return 5;}
          }
        }
      ],

      styles:{
        th:{
          fontSize:5.6,
          bold:true,
          color:'#FFFFFF',
          fillColor:'#17324D',
          alignment:'center'
        },
        td:{
          fontSize:5.8,
          color:'#253858',
          noWrap:false
        },
        tdn:{
          fontSize:5.8,
          color:'#253858',
          alignment:'right',
          noWrap:false
        },
        totalLabel:{
          fontSize:6.2,
          bold:true,
          color:'#17324D'
        },
        totalMoney:{
          fontSize:6.2,
          bold:true,
          color:'#17324D',
          alignment:'right'
        },
        totalMoneyDanger:{
          fontSize:6.2,
          bold:true,
          color:'#C9372C',
          alignment:'right'
        }
      }
    };

  return doc;
}


self.onmessage = ({data}) => {
  try {
    const build = { ingresos, egresos }[data.kind];
    if (!build) throw new Error('Reporte desconocido.');
    pdfMake.createPdf(build(data)).getBuffer(buffer => {
      const bytes = new Uint8Array(buffer.buffer, buffer.byteOffset, buffer.byteLength);
      const pdf = bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength);
      self.postMessage({pdf}, [pdf]);
    });
  } catch (error) {
    self.postMessage({error: true});
  }
};
