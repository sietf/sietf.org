// JavaScript code for sieft.org
// 

function easeInOut(minValue,maxValue,totalSteps,actualStep,powr) { 
//Generic Animation Step Value Generator By www.hesido.com 
    var delta = maxValue - minValue; 
    var stepp = minValue+(Math.pow(((1 / totalSteps) * actualStep), powr) * delta); 
    return Math.ceil(stepp) 
    } 

function widthChange() { 
    if (!this.currentWidth) this.currentWidth = 150; 
	//if no memory is set, set it first; 
    doWidthChangeMem(this,this.currentWidth,170,10,10,0.5); 
    } 
	
function widthRestore() { 
    if (!this.currentWidth) return; 
    doWidthChangeMem(this,this.currentWidth,150,10,10,0.5); 
    }

function doWidthChangeMem(elem,startWidth,endWidth,steps,intervals,powr) { 
//Width changer with Memory by www.hesido.com
    if (elem.widthChangeMemInt)
	window.clearInterval(elem.widthChangeMemInt);
    var actStep = 0;
    elem.widthChangeMemInt = window.setInterval(
	function() { 
	  elem.currentWidth = easeInOut(startWidth,endWidth,steps,actStep,powr);
	  elem.style.width = elem.currentWidth + "px"; 
	  actStep++;
	  if (actStep > steps) window.clearInterval(elem.widthChangeMemInt);
	} 
	,intervals)
}

function getCookieVal(name)
{
	if (document.cookie.length > 0)
	{ 
		start = document.cookie.indexOf(name + "=");
			if (start != -1)
			{ 
				start = start + name.length + 1; 
				end   = document.cookie.indexOf(";", start);
				if (end == -1) 
					end = document.cookie.length;
				return document.cookie.substring(start, end);
			} 
	}
	return null;
}

function setCookie(name, value, expiredays)
{
	var exdate = new Date();
	exdate.setTime(exdate.getTime() + (expiredays*24*3600*1000));
	document.cookie = name + "=" + value + ((expiredays == null) ? "" : "; expires=" + exdate);
}

function checkShowInfoCookie()
{
	if (document.getElementById('infobar') != null) {
	show = getCookieVal('showinfo')
	if (show != null)
  	{
		if (show == 1) 
		{
			document.getElementById('infobar').style.display = 'block';
		}
		else
		{
			document.getElementById('infobar').style.display = 'none';
		}
	}
	}
}

function show_quick()
{
	if (document.getElementById('infobar'))
	{
		document.getElementById('infobar').style.display = 'block';
		document.getElementById('q').style.width = 190+'px';
		//document.getElementById('q').style.left = 646+'px';
		document.getElementById('q').style.backgroundColor = '#6699cc';
		document.getElementById('q').style.height = '180px';
	}
}

function hide_quick()
{
	if (document.getElementById('infobar'))
	{
		document.getElementById('infobar').style.display = 'none';
		document.getElementById('q').style.width = 100+'px';
		//document.getElementById('q').style.left = 771+'px';
		document.getElementById('q').style.backgroundColor = '';
		document.getElementById('q').style.height = 20+'px';
	}
}