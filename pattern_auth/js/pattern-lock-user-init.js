jQuery(document).ready(function(){

    var patternInput = document.getElementById('pattern_auth');
    // Init pattern auth
    var lock = new PatternLock('#patternHolder',{
          margin : 15,
          radius: 25,
          patternVisible: true,
          lineOnMove: true,
          enableSetPattern : true,
          onDraw:function(pattern){
              // Store pattern in hidden input
              patternInput.value = pattern;
          }
      }); 
      
      // Set pattern visible on edit user screen
      lock.setPattern(pattern_auth_data.pattern_set); 
      
});