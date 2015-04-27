jQuery(document).ready(function(){
    
    // hide pattern auth input field
    document.getElementById('pattern_auth').style.display = 'none';  
    var patternInput = document.getElementById('pattern_auth');
    // Init pattern auth
    var lock = new PatternLock('#patternHolder',{
          margin : 15,
          radius: 25,
          patternVisible: true,
          lineOnMove: true,
          onDraw:function(pattern){
              // Store pattern in hidden input
              patternInput.value = pattern;
          }
      }); 
      
});