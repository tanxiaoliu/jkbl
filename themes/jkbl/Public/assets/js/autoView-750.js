!function(e){function t(){var t=m.getBoundingClientRect().width;t/i>r&&(t=r*i),e.rem=t/(r/o),m.style.fontSize=e.rem+"px"}var i,a,n,r=750,o=50,l=e.document,m=l.documentElement,s=l.querySelector('meta[name="viewport"]'),d=l.querySelector('meta[name="flexible"]');if(s){var c=s.getAttribute("content").match(/initial\-scale=(["']?)([\d\.]+)\1?/);c&&(a=parseFloat(c[2]),i=parseInt(1/a))}else if(d){var c=d.getAttribute("content").match(/initial\-dpr=(["']?)([\d\.]+)\1?/);c&&(i=parseFloat(c[2]),a=parseFloat((1/i).toFixed(2)))}if(!i&&!a){var p=(e.navigator.appVersion.match(/android/gi),e.navigator.appVersion.match(/iphone/gi));i=e.devicePixelRatio,i=p?i>=3?3:i>=2?2:1:1,a=1/i}if(m.setAttribute("data-dpr",i),!s)if(s=l.createElement("meta"),s.setAttribute("name","viewport"),s.setAttribute("content","initial-scale="+a+", maximum-scale="+a+", minimum-scale="+a+", user-scalable=no"),m.firstElementChild)m.firstElementChild.appendChild(s);else{var u=l.createElement("div");u.appendChild(s),l.write(u.innerHTML)}e.dpr=i,e.addEventListener("resize",function(){clearTimeout(n),n=setTimeout(t,30)},!1),e.addEventListener("pageshow",function(e){e.persisted&&(clearTimeout(n),n=setTimeout(t,30))},!1),t()}(window);