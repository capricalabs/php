<!--
//
//
function getObj(name)
	{
	if (document.getElementById)
		{
		return document.getElementById(name);
		}
	else if (document.all)
		{
		return document.all[name];
		}
	else
		{
		return false;
		}
	}
//
//
function getNumSelected(param)
	{
	if (s = getObj(param))
		{
		if (s.selectedIndex)	return parseInt(s.options[s.selectedIndex].value);
		else					return parseInt(s.value);
		}
	return 0;
	}
//
//
function numDaysIn(mn,yr)		// mn = 0-11, yr = actual
	{
	if (mn < 0) { mn += 12; yr--; }
	if (mn==3 || mn==5 || mn==8 || mn==10)	return 30;
	else if ((mn==1) && leapYear(yr))		return 29;
	else if (mn==1)							return 28;
	else									return 31;
	}
//
//
function leapYear(yr)
	{
	if (((yr % 4 == 0) && yr % 100 != 0) || yr % 400 == 0) return true;
	return false;
	}
//
//
function arr()
	{
	this.length = arr.arguments.length;
	for (n=0; n < arr.arguments.length; n++) { this[n] = arr.arguments[n]; }
	}
//
//var cur = new Date();
//
function getWeekDay(mth,day,yr)
	{
	first_day = firstDayOfYear(yr);
	for (num = 0; num < mth; num++) { first_day += numDaysIn(num,yr); }
	first_day += day-1;
	return first_day%7;
	}

function firstDayOfYear(yr)
	{
	diff = yr - 401;
	return parseInt((1 + diff + (diff / 4) - (diff / 100) + (diff / 400)) % 7);
	}
//
// fixes a Netscape 2 and 3 bug
//
function getFullYear(d)
	{	// d is a date object
	yr = d.getYear();
	if (yr < 1000) yr+=1900;
	return yr;
	}
//
//
weekdays = new arr("Sunday","Monday","Tuesday","Wednesday", "Thursday","Friday","Saturday");

months = new arr("January","February","March","April","May","June","July","August","September","October","November","December");

months_in_year = new arr(00,1,2,3,4,5,6,7,8,9,10,11);

hours_in_day = new arr(00,1,2,3,4,5,6,7,8,9,10,11);

minutes_in_hour = new arr(00,15,30,45);

ampm = new arr('AM','PM');
//
//
function changeDays(dateID, sel)
	{
	mn = getNumSelected(dateID+"Month");
	yr = getNumSelected(dateID+"Year");
	dy = getObj(dateID+"Day");
//	<option value="">Day..</option>
	dy.options[0].text = 'Day..';
	numDays = numDaysIn(mn,yr);
	dy.options.length = numDays+1;
//alert(numDays)
	for (i=1; i<=numDays; i++)
		{
	//	j=i+1;
		dy.options[i].text = i;
		dy.options[i].value = i;
		if (i == sel) dy.options[i].selected = "YES";
		}
	}
//
//
function DateCmp(d1,d2)
	{
	if (d1.getYear() > d2.getYear()) return 1;
	if (d1.getYear() < d2.getYear()) return -1;
	if (d1.getMonth() > d2.getMonth()) return 1;
	if (d1.getMonth() < d2.getMonth()) return -1;
	if (d1.getDate() > d2.getDate()) return 1;
	if (d1.getDate() < d2.getDate()) return -1;
	if (d1.getHours() > d2.getHours()) return 1;
	if (d1.getHours() < d2.getHours()) return -1;
	if (d1.getMinutes() > d2.getMinutes()) return 1;
	if (d1.getMinutes() < d2.getMinutes()) return -1;
	if (d1.getSeconds() > d2.getSeconds()) return 1;
	if (d1.getSeconds() < d2.getSeconds()) return -1;
	return 0;
	}
//
//
function SameDay(d1,d2)
	{
	if (d1.getYear() > d2.getYear()) return false;
	if (d1.getYear() < d2.getYear()) return false;
	if (d1.getMonth() > d2.getMonth()) return false;
	if (d1.getMonth() < d2.getMonth()) return false;
	if (d1.getDate() > d2.getDate()) return false;
	if (d1.getDate() < d2.getDate()) return false;
	return true;
	}
//
//
function checkForm(form)
	{
	yrF = getObj("published_from_Year").options[getObj("published_from_Year").selectedIndex].value;
	mnF = getObj("published_from_Month").options[getObj("published_from_Month").selectedIndex].value;
	dyF = getObj("published_from_Day").options[getObj("published_from_Day").selectedIndex].value;
//alert(yrF+" "+mnF+" "+dyF);
	if ((yrF || mnF || dyF) && (!yrF || !mnF || !dyF))
		{
		alert ("Invalid Published From date");
		return false;
		}

	yrT = getObj("published_to_Year").options[getObj("published_to_Year").selectedIndex].value;
	mnT = getObj("published_to_Month").options[getObj("published_to_Month").selectedIndex].value;
	dyT = getObj("published_to_Day").options[getObj("published_to_Day").selectedIndex].value;
	if ((yrT || mnT || dyT) && (!yrT || !mnT || !dyT))
		{
		alert ("Invalid Published To date");
		return false;
		}

	if (!yrF && !mnF && !dyF)
		{
		getObj("published_from_Date").value = '';
		from = 0;
		}
	else
		{
		getObj("published_from_Date").value = months[mnF]+" "+dyF+", "+yrF;
		from = Number(new Date(months[mnF]+" "+dyF+" "+yrF));
		}

	if (!yrT && !mnT && !dyT)
		{
		getObj("published_to_Date").value = '';
		to = 0;
		}
	else
		{
		getObj("published_to_Date").value = months[mnT]+" "+dyT+", "+yrT;
		to = Number(new Date(months[mnT]+" "+dyT+" "+yrT));
		}

//alert (from+" "+to);
	if (from > to)
		{
		alert("From date is after to date");
		return false;
		}

	return true;
	}
//
//-->
