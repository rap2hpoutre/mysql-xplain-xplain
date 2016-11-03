/*
  Utilisation de TAB pour indenter le texte dans les textarea
  Source: http://stackoverflow.com/a/14166052
 */

var textareas = document.getElementsByTagName('textarea');
var count = textareas.length;
for(var i=0;i<count;i++){
    textareas[i].onkeydown = function(e){
        if(e.keyCode==9 || e.which==9){
            e.preventDefault();
            var s = this.selectionStart;
            var tab = "\t";
            this.value = this.value.substring(0,this.selectionStart) + tab + this.value.substring(this.selectionEnd);
            this.selectionEnd = s + tab.length; 
        }
    }
}

