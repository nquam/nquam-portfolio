var xui_ajaxform = {
    /**
     * initializes our object
     * 
     * @return void
     */
    init: function()
    {
        // subscribe to our global updated dom event
        updated_dom_event.subscribe(this.bind_events);
    },
    
    /**
     * attaches listeners to the events we care about
     * 
     * @return void
     */
    bind_events: function()
    {
        // need to bind the the onsubmit event for our forms
        x$('form').un('submit').on('submit', function(event){xui_ajaxform.handle_form_submit(event);});
        
        x$('form#frm-user-login button').un('click').on('click', function(event){
            xui_ajaxform.handle_login_form_click(event);
        });

        x$('#button-delete-schedule').un('click').on('click', function(event){
            xui_ajaxform.handle_delete_schedule_click(event);
        });
    },
    
    /**
     * handles the click of delete schedule button
     * 
     */
    handle_delete_schedule_click: function (e)
    {
        if (confirm('Are you sure you want to delete this schedule?')) {
            var form = x$('form#frm-delete-schedule');
            form.fire('submit');
        }
    },
    
    /**
     * handles the click of a button on the user login form
     * 
     */
    handle_login_form_click: function (e)
    {
        if (e.target.value.indexOf('frm_reset_password') != -1) {
            if (!confirm('Do you wish to reset your password?')) {
                return;
            }
        }

        var form = x$('form#frm-user-login');
        form[0].action = e.target.value;
        form.fire('submit');
    },

    /**
     * this listner for forms being submitted
     * 
     * @param event event the event that we were listening for
     * @return void
     */
    handle_form_submit: function(event)
    {
        // stop our form right away because we want to submit it with xhr
        event.preventDefault();
        
        // get all of our values
        var query_string = '?';
        var i = 0;
        x$('input', event.target).each(function(element){
console.log(element);
            var key = x$(element).attr('name');            
            var value = document.getElementById(x$(element).attr('id')).value;
            query_string += '&' + key + '=' + value;
            i++;
        });
console.log(query_string);
        
        // make our xhr
        x$(event.target).xhr(x$(event.target).attr('action'), {
            data: query_string,
            method: x$(event.target).attr('method'),
            callback: function(){
                //alert(this.responseText);
                var response = eval('(' + this.responseText + ')');
                if ('error' == response.success){
                    // show them an error
                    alert(response.errors);
                } else {
                    console.log(response);
                    // handle the success response, we have to do some
                    // custom stuff for some of the forms
                    switch (response.form_name){
                        case 'frm_update_child':
                            xui_ajaxform.handle_success_form_update_child(response.data);
                            break;
                            
                        case 'frm_submit_child':
                            xui_ajaxform.handle_success_form_submit_child(response.data);
                            break;
                            
                        case 'frm_submit_received_date':
                            xui_ajaxform.handle_success_form_submit_received_date(response.data);
                            break;
                            
                        case 'frm_delete_schedule':
                        case 'frm_registration':
                        case 'frm_authentication':
                            if ('desktop' == response.method){
                                window.location = '/listschedules';
                            } else {
                                go_to_schedule_page.fire();
                            }
                            break;
                            
                        case 'frm_reset_password':
                            alert('Your password was reset. Please check your email.');
                            break;
                            
                        case 'frm_set_reminders':
                            // hide the form
                            x$('#' + response.form_name).removeClass('show').addClass('hide');
                            x$('#frm_success_cancel_message').removeClass('show').addClass('hide');
                            // show the success message
                            x$('#frm_success_message').removeClass('hide').addClass('show');
                            break;
                            
                        case 'frm_cancel_reminders':
                            // hide the form
                            x$('#' + response.form_name).removeClass('show').addClass('hide');
                            x$('#frm_success_message').removeClass('show').addClass('hide');
                            // show the success message
                            x$('#frm_success_cancel_message').removeClass('hide').addClass('show');
                            break;
                            
                        default:
                            // hide the form
                            x$('#' + response.form_name).removeClass('show').addClass('hide');
                        
                            // show the success message
                            x$('#frm_success_message').removeClass('hide').addClass('show');
                            break;
                    }
                }
            }
        });
    },
    
    /**
     * handles what happens after the submit received date form has been
     * successfully submitted
     * 
     * @param json data the data coming back from our xhr
     * @return void
     */
    handle_success_form_submit_received_date: function(data)
    {
        // hide our form
        x$('#frm-submit-received-date').removeClass('show').addClass('hide');
        
        // show our user details
        x$('#user-shot-details').removeClass('hide').addClass('show');
        
        // populate our data
        x$('#txtholder-shot_d').html(data.shot_d);
        x$('#txtholder-shot_m').html(data.shot_m_readable);
        x$('#txtholder-shot_y').html(data.shot_y);
    },
    
    /**
     * handles what happens after the submit child form has been
     * successfully submitted
     * 
     * @param json data the data coming back from our xhr
     * @return void
     */
    handle_success_form_submit_child: function(data)
    {
        var li = '<li><a href="' + 
            data.url + 
            '">' + 
            data.name + 
            '<span class="icon icon-arrow"></span></a></li>';
        x$('#schedules').bottom(li);
        
        setTimeout(function(){
            // rebind all our events because XUI doesn't have a live() bind like jquery
            updated_dom_event.fire();
        }, 600);
    },
    
    /**
     * handles what happens after the update child form has been
     * successfully submitted
     * 
     * @param json data the data coming back from our xhr
     * @return void
     */
    handle_success_form_update_child: function(data)
    {
        // hide our form
        x$('#frm-update-child').removeClass('show').addClass('hide');
        
        // show our edit button again
        x$('#button-update-child').removeClass('hide').addClass('show');
        console.log(data);
        
        // update the display data
        x$('.txtholder-name').html(data.name);
        x$('.txtholder-dob_d').html(data.shot_d);
        x$('.txtholder-dob_m').html(data.shot_m_readable);
        x$('.txtholder-dob_y').html(data.shot_y);
        
        setTimeout(function(){
            // rebind all our events because XUI doesn't have a live() bind like jquery
            updated_dom_event.fire();
        }, 600);
    }
}
