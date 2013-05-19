// Animate (11-January-2010)
// by Vic Phillips http://www.vicsjavascripts.org.uk

// To progressively change the Left, Top, Width, Height or Opacity of an element over a specified period of time.
// With the ability to scale the effect time on specified minimum/maximum values
// and with three types of progression 'sin' and 'cos' and liner.

// **** Application Notes

// **** The HTML Code
//
// when moving an element the inline or class rule style position of the element should be assigned as
// 'position:relative;' or 'position:absolute;'
//
// The element would normally be assigned a unique ID name.
//

// **** Initialising the Script.
//
// The script is initialised by assigning an instance of the script to a variable.
// e.g A = new zxcAnimate('left','id1')
// where:
//  A           = a global variable                                                               (variable)
//  parameter 0 = the mode(see Note 1).                                                           (string)
//  parameter 1 = the unique ID name or element object.                                           (string or element object)
//  parameter 1 = the initial value.                                                              (digits, default = 0)

// **** Executing the Effect
//
// The effect is executed by an event call to function 'A.animate(10,800 ,5000,[10,800]);'
// where:
//  A           = the global referencing the script instance.                                 (variable)
//  parameter 0 = the start value.                                                            (digits, for opacity minimum 0, maximum 100)
//  parameter 1 = the finish value.                                                           (digits, for opacity minimum 0, maximum 100)
//  parameter 2 =  period of time between the start and finish of the effect in milliseconds. (digits or defaults to previous or 0(on first call) milliSeconds)
//  parameter 3 = (optional) to scale the effect time on a specified minimum/maximum.         (array, see Note 3)
//                 field 0 the minimum value. (digits)
//                 field 1 the maximum value. (digits)
//  parameter 3 = (optional) the type of progression, 'sin', 'cos' or 'liner'.                (string, default = 'liner')
//                 'sin' progression starts fast and ends slow.
//                 'cos' progression starts slow and ends fast.
//
//  Note 1:  Examples modes: 'left', 'top', 'width', 'height', 'opacity.
//  Note 2:  The default units(excepting opacity) are 'px'.
//           For hyphenated modes, the first character after the hyphen must be upper case, all others lower case.
//  Note 3:  The scale is of particular use when re-calling the effect
//           in mid progression to retain an constant rate of progression.
//  Note 4:  The current effect value is recorded in A.data[0].
//  Note 5:  A function may be called on completion of the effect by assigning the function
//           to the animator intance property .Complete.
//           e.g. [instance].Complete=function(){ alert(this.data[0]); };
//



// **** Functional Code(1.58K) - NO NEED to Change

function zxcAnimate(mde,obj,srt){
 this.to=null;
 this.obj=typeof(obj)=='object'?obj:document.getElementById(obj);
 this.mde=mde.replace(/\W/g,'');
 this.data=[srt||0];
 return this;
}

zxcAnimate.prototype.animate=function(srt,fin,ms,scale,c){
 clearTimeout(this.to);
 this.time=ms||this.time||0;
 this.neg=srt<0||fin<0;
 this.data=[srt,srt,fin];
 this.mS=this.time*(!scale?1:Math.abs((fin-srt)/(scale[1]-scale[0])));
 this.c=typeof(c)=='string'?c.charAt(0).toLowerCase():this.c?this.c:'';
 this.inc=Math.PI/(2*this.mS);
 this.srttime=new Date().getTime();
 this.cng();
}

zxcAnimate.prototype.cng=function(){
 var oop=this,ms=new Date().getTime()-this.srttime;
 this.data[0]=(this.c=='s')?(this.data[2]-this.data[1])*Math.sin(this.inc*ms)+this.data[1]:(this.c=='c')?this.data[2]-(this.data[2]-this.data[1])*Math.cos(this.inc*ms):(this.data[2]-this.data[1])/this.mS*ms+this.data[1];
 this.apply();
 if (ms<this.mS) this.to=setTimeout(function(){oop.cng()},10);
 else {
  this.data[0]=this.data[2];
  this.apply();
  if (this.Complete) this.Complete(this);
 }
}

zxcAnimate.prototype.apply=function(){
 if (isFinite(this.data[0])){
  if (this.data[0]<0&&!this.neg) this.data[0]=0;
  if (this.mde!='opacity') this.obj.style[this.mde]=Math.floor(this.data[0])+'px';
  else zxcOpacity(this.obj,this.data[0]);
 }
}

function zxcOpacity(obj,opc){
 if (opc<0||opc>100) return;
 obj.style.filter='alpha(opacity='+opc+')';
 obj.style.opacity=obj.style.MozOpacity=obj.style.WebkitOpacity=obj.style.KhtmlOpacity=opc/100-.001;
}

function Bar(o){
 var obj=document.getElementById(o.ID);
 this.oop=new zxcAnimate('width',obj,0);
 this.max=obj.parentNode.offsetWidth;
 this.to=null;
}

Bar.prototype={

 Start:function(sec){
  clearTimeout(this.to);
  this.oop.animate(this.max,0,sec*1000);
  this.srt=new Date();
  this.sec=sec;
  this.Time();
 },

 Time:function(sec){
  var oop=this,sec=this.sec-Math.floor((new Date()-this.srt)/1000);
  //this.oop.obj.innerHTML=sec+' seconds';
  if (sec>0){
   this.to=setTimeout(function(){ oop.Time(); },1000);
  }
   else {
    window.location.replace(document.location);
   }
 }

}

