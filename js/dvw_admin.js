jQuery(document).ready(function($){
    "use strict";
    
//    API and city warnings
    $('#api').focus(function(){
        $(this).removeClass('dvw-warning');
    });
    
    $('#api').blur(function (){
        if($(this).val() == ''){
            $(this).addClass('dvw-warning');
        }
    });
    
    $('#city-text').focus(function(){
        $(this).removeClass('dvw-warning');
    });
    
    $('#city-text').blur(function (){
        if($(this).val() == ''){
            $(this).addClass('dvw-warning');
        }
    });
    
//    show/hide city field and dynamic-fail checkbox
    if($('#static').prop( "checked" )){
//        add static checked class
        $('label[for=static]').addClass('static-checked');
        $('#city').show();
        $('#city input[type=text]').attr('required', 'required');
        $('#dynamic-fail').hide();
    }else{
//        remove static checked class
        $('label[for=static]').removeClass('static-checked');
        $('#city').hide();
        $('#dynamic-fail').show();
    }
    
    $('#static').click(function (){
        if($(this).prop( "checked" )){
//        add static checked class
            $('label[for=static]').addClass('static-checked');
            $('label[for=dynamic]').removeClass('dynamic-checked');
            $('#city').show();
            $('#city input[type=text]').attr('required', 'required');
            $('#dynamic-fail').hide();
        }else{
//        remove static checked class
            $('label[for=static]').removeClass('static-checked');
            $('#city').hide();
            $('#dynamic-fail').show();
        }
    });
    
    if($('#dynamic').prop('checked')){
        $('label[for=dynamic]').addClass('dynamic-checked');
    }
    
    $('#dynamic').click(function (){
        if ($(this).prop("checked") && !$('#dynamic-fail-check').prop( "checked" )){
            $('#city').hide();
            $('#city input[type=text]').removeAttr('required');
            $('#dynamic-fail').show();
        }
        if($(this).prop("checked")){
            //            add dynamic checked class
            $('label[for=dynamic]').addClass('dynamic-checked');
            $('label[for=static]').removeClass('static-checked');
            $('#dynamic-fail').show();
        }
    });
    
    if($('#dynamic-fail-check').prop( "checked" )){
        $('#city').show();
        $('#city input[type=text]').attr('required', 'required');
    }
    
    $('#dynamic-fail-check').click(function(){
        if($(this).prop( "checked" )){
            //        add dynamic checked class
            $(this).addClass('dynamic-checked');
            $('#city').show();
            $('#city input[type=text]').attr('required', 'required');
        }else{
//            remove dynamic checked class
            $(this).removeClass('dynamic-checked');
            $('#city').hide();
            $('#city input[type=text]').removeAttr('required');
        }
    });
    
//    show appropriate metric unit
    if($('#celsius').prop("selected")){
        $('#c').show();
        $('#f').hide();
    }else if ( $('#fahrenheit').prop("selected")) {
        $('#c').hide();
        $('#f').show();
    }else{
        $('#c').hide();
        $('#f').hide();
    }
    
    $('#temp').change(function(){
        if($(this).val() === 'f'){
            $('#c').hide();
            $('#f').show();
        }else if($(this).val() === 'c'){
            $('#c').show();
            $('#f').hide();
        }
    });
    
});