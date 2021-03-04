(function($) {
    // USE STRICT
    "use strict";
    var allow_custom_amount = true;
    var campaign = $('#wpsd_campaign').val();
    var campaign_id = $('#wpsd_campaign_id').val();
    var grouped_campaigns = groupSimilarCampaignsIds();
    var fund = $('#wpsd_fund').val();
    var fund_id = $('#wpsd_fund_id').val();
    var grouped_funds = groupSimilarFundsIds();
    var in_memory_of_field = $('#wpsd_in_memory_of_field').val();
    $('input#wpsd_media_manager').click(function(e) {

        e.preventDefault();
        var image_frame;
        var prevInputId = $(this).prev().attr('id');
        var previewDivId = $(this).next().attr('id');
        var imgType = $(this).data("image-type");
        if (image_frame) {
            image_frame.open();
        }
        // Define image_frame as wp.media object
        image_frame = wp.media({
            title: 'Select Media',
            multiple: false,
            library: {
                type: 'image',
            }
        });

        image_frame.on('close', function() {
            // On close, get selections and save to the hidden input
            // plus other AJAX stuff to refresh the image preview
            var selection = image_frame.state().get('selection');
            var gallery_ids = new Array();
            var my_index = 0;
            selection.each(function(attachment) {
                gallery_ids[my_index] = attachment['id'];
                my_index++;
            });
            var ids = gallery_ids.join(",");
            $('input#' + prevInputId).val(ids);
            Refresh_Image(ids, previewDivId, imgType);
        });

        image_frame.on('open', function() {
            // On open, get the id from the hidden input
            // and select the appropiate images in the media manager
            var selection = image_frame.state().get('selection');
            var ids = $('input#' + prevInputId).val().split(',');
            ids.forEach(function(id) {
                var attachment = wp.media.attachment(id);
                attachment.fetch();
                selection.add(attachment ? [attachment] : []);
            });

        });

        image_frame.open();
    });

    // Ajax request to refresh the image preview
    function Refresh_Image(the_id, preview_id, img_type) {
        var data = {
            action: 'wpsd_get_image',
            id: the_id,
            prev_id: preview_id,
            img_type: img_type
        };

        $.get(ajaxurl, data, function(response) {

            if (response.success === true) {
                //alert(response.data.image);
                $('#' + preview_id).replaceWith(response.data.image);
            }
        });
    }
    addCampaignIds();
    addFundIds();
    changeParams();
    $('#wpsd_allow_custom_amount').on('change', function(){
        allow_custom_amount = $(this).prop("checked");
        changeParams();
    });
    $('#wpsd_campaign').on('change', function(){
        campaign = $(this).val();
        if(!campaign){
            campaign_id = null;
        }
        addCampaignIds();
        changeParams();
    });
    $('#wpsd_campaign_id').on('change', function(){
        campaign_id = $(this).val();
        const options = getSelectedCampaignOptions();
        if(options){
            campaign = options[0]['name'];
        }
        changeParams();
    });
    $('#wpsd_fund').on('change', function(){
        fund = $(this).val();
        if(!fund){
            fund_id = null;
        }
        addFundIds();
        changeParams();
    });
    $('#wpsd_fund_id').on('change', function(){
        fund_id = $(this).val();
        const options = getSelectedFundOptions();
        if(options){
            fund = options[0]['name'];
        }
        changeParams();
    });

    $('#wpsd_in_memory_of_field').on('change', function(){
        in_memory_of_field = $(this).val();
        changeParams();
    });

    function changeParams(){
        var code = `[wp_stripe_donation custom_amount="${allow_custom_amount}"`;
        const campaignOptions = getSelectedCampaignOptions();
        const fundOptions = getSelectedFundOptions();
        if(campaignOptions){
            code += ' campaign="' + campaignOptions[0]['name'] +'"';
        }
        if(campaign_id && campaign_id.length){
            code += ' campaign_id="' + campaign_id +'"';
        }
        if(fundOptions){
            code += ' fund="' + fundOptions[0]['name'] +'"';
        }
        if(fund_id && fund_id.length){
            code += ' fund_id="' + fund_id +'"';
        }

        if(in_memory_of_field && in_memory_of_field.length){
            code += ' imof="' + in_memory_of_field +'"';
        }

        code += ']';
        $('#wpsd_shortcode').val(code);
    }
    var currency = wpsdAdminScript.currency;
    addCurrencyFieldType();
    function addCurrencyFieldType(){
        // amount:
        var currencyInput = document.querySelector('input[type="currency"]')
        if(!currencyInput){
            return;
        }
        // format initial value
        onBlur({target:currencyInput})

        // bind event listeners
        currencyInput.addEventListener('focus', onFocus)
        currencyInput.addEventListener('blur', onBlur)
    }
    function addCampaignIds(){
        var campaignOptions = getSelectedCampaignOptions();
        var campaignOptionsStr = "<option value=''>Select option</option>";
        if(campaignOptions){
            var selected = campaignOptions.length === 1;
            campaignOptions.forEach((item) => {
                campaignOptionsStr += `<option value="${item['id']}" selected="${(item['id'].toString() === fund || selected)}? 'selected': ''">${item['id']}</option>`;
            });
            $('#wpsd_campaign_id').html(campaignOptionsStr);
            if(selected){
                campaign_id = campaignOptions[0]['id'].toString();
            }
        }else{
            $('#wpsd_campaign_id').html(campaignOptionsStr);
        }
    }
    function addFundIds(){
        var fundOptions = getSelectedFundOptions();
        var fundOptionsStr = "<option value=''>Select option</option>";
        if(fundOptions){
            var selected = fundOptions.length === 1;
            fundOptions.forEach((item) => {
                fundOptionsStr += `<option value="${item['id']}" selected="${(item['id'].toString() === fund || selected)}? 'selected': ''">${item['id']}</option>`;
            });
            $('#wpsd_fund_id').html(fundOptionsStr);
            if(selected){
                fund_id = fundOptions[0]['id'].toString();
            }
        }else{
            $('#wpsd_fund_id').html(fundOptionsStr);
        }
    }
    function getSelectedCampaignOptions(){
        if( !window.hasOwnProperty('kindfulCampaigns')) {
            return;
        }
        var selected_campaign_name = $('#wpsd_campaign').val();
        var found_item = null;
        kindfulCampaigns.forEach((item) => {
            if(item['name'] === selected_campaign_name){
                found_item = item;
            }
        });
        if(found_item) {
            return grouped_campaigns[found_item['name']];
        }
        return null;
    }
    function getSelectedFundOptions(){
        if( !window.hasOwnProperty('kindfulFunds')) {
            return;
        }
        var selected_fund_name = $('#wpsd_fund').val();
        var found_item = null;
        kindfulFunds.forEach((item) => {
            if(item['name'] === selected_fund_name){
                found_item = item;
            }
        });
        if(found_item) {
            return grouped_funds[found_item['name']];
        }
        return null;
    }
    function groupSimilarCampaignsIds(){
        if( !window.hasOwnProperty('kindfulCampaigns')) {
            return;
        }
        var campaigns = [];
        // find the selected campaign name:
        kindfulCampaigns.forEach((item) => {
            const keys = Object.keys(campaigns);
            if(keys.includes(item['name'])) {
                campaigns[item['name']].push(item);
            }else{
                campaigns[item['name']] = [item];
            }
        });
        return campaigns;
    }

    function groupSimilarFundsIds(){
        if( !window.hasOwnProperty('kindfulFunds')) {
            return;
        }
        var funds = [];
        // find the selected fund name:
        kindfulFunds.forEach((item) => {
            const keys = Object.keys(funds);
            if(keys.includes(item['name'])) {
                funds[item['name']].push(item);
            }else{
                funds[item['name']] = [item];
            }
        });
        return funds;
    }
    function localStringToNumber( s ){
        return Number(String(s).replace(/[^0-9.-]+/g,""))
    }

    function onFocus(e){
        if(!e.target){
            return;
        }
        var value = e.target.value;
        e.target.value = value ? localStringToNumber(value): "0";
    }

    function onBlur(e){
        if(!e.target){
            return;
        }
        var value = e.target.value

        var options = {
            maximumFractionDigits : 2,
            currency              : currency,
            style                 : "currency",
            currencyDisplay       : "symbol"
        }

        e.target.value = value
            ? localStringToNumber(value).toLocaleString(undefined, options)
            : ''
    }

})(jQuery);