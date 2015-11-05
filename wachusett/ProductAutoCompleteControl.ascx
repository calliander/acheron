<%@ Control Language="C#" AutoEventWireup="true" CodeFile="ProductAutoCompleteControl.ascx.cs" Inherits="CMSFormControls_Wachusett_ProductAutoCompleteControl" %>

<script type="text/javascript">

    /**
     * jQuery Ready
     *
     * Scripts to run when the document is ready.
     */
    $(function () {

        // Get the product list in advance.
        GetProductList();
        // If there's a product ID, get the name.
        if($('input.hidden-product').val()) GetProductName();
        // Get the variant list in advance.
        GetVariantList();
        // If there's a variant ID, get the name.
        if($('input.hidden-variant').val()) GetVariantName();

    });

    /**
     * Get Product Name
     *
     * Retrieves the name of an individual product from the store web service.
     *
     * @return mixed Updates the textbox with the product name
     */
    function GetProductName() {

        // Fetch needed values.
        var wsurl = $settings.webservice.val('url');
        var pid = $('input.hidden-product').val();

        // AJAX call to the web service for the product name from ID.
        $.ajax({
            url: wsurl + '/api/Store/GetBasicProduct/' + pid,
            dataType: 'json',
            type: 'GET',
            contentType: 'application/json; charset=utf-8',
            success: function(data) {
                // Update the text box.
                $('#txtProductListAC').val(data.Name);
            }
        });
    }

    /**
     * Get Product List
     *
     * Loops through the different categories and builds the list of product
     * names to use in the autocomplete box.
     *
     * @return mixed Creates a jQuery autocomplete instance on a text box
     */
    function GetProductList() {

        // Fetch needed values.
        var wsurl = $settings.webservice.val('url');
        var masterUrl = wsurl + '/api/Store/GetProductsListByType/';
        var masterList = [];

        // Looping AJAX calls to the web service for the different categories.
        for(var i = 1; i < 10; i++)
        {
            $.ajax({
                async: false,
                url: masterUrl + i,
                dataType: 'json',
                type: 'GET',
                contentType: 'application/json; charset=utf8',
                success: function(data) {
                    // Map function for pushing data to the master list.
                    $.map(data, function(item) {
                        masterList.push({label: item.Name, val: item.ProductID});
                    });
                },
                // TODO: Make Kerry decide on how to handle services being down.
                error: function(response) {
                    console.log('Error in loop.');
                },
                failure: function(response) {
                    console.log('Failure in loop.');
                }
            });
        }

        // Create the autocomplete instance on the text box.
        $("#txtProductListAC").autocomplete({
            source: masterList,
            minLength: 1,
            delay: 1000,
            select: function (e, i) {
                // Update the boxes.
                $("input.hidden-product").attr('value', i.item.val);
                $("#hdnProductName").val(i.item.label);
            },
        });

    }

    /**
     * Get Variant Name
     *
     * Retrieves the name of an individual variant from the store web service.
     *
     * @return mixed Updates the textbox with the variant name
     */
    function GetVariantName() {

        // Fetch needed values.
        var wsurl = $settings.webservice.val('url');
        var productId = $('input.hidden-product').val();

        // AJAX call to the web service for the variant name from ID.
        $.ajax({
            url: wsurl + '/api/Store/GetBasicProduct/' + productId,
            dataType: 'json',
            type: 'GET',
            contentType: 'application/json; charset=utf-8',
            success: function(data) {
                // No individual variant call. Map function searches the returned data.
                var array = $.map(data.ProductVariants, function(item) {
                    if(item.VariantID == $('input.hidden-variant').val())
                    {
                        return {
                            label: item.Name,
                            val: item.VariantID
                        }
                    }
                });
                // Update the text box.
                $('#txtVariantListAC').val(array[0].label);
            }
        });
    }

    /**
     * Get Variant List
     *
     * Gets the parent product info and builds the list of variant names to use
     * in the autocomplete box.
     *
     * @return mixed Creates a jQuery autocomplete instance on a text box
     */
    function GetVariantList() {

        // Fetch needed values.
        var wsurl = $settings.webservice.val('url');

        // Create the autocomplete instance on the text box.
        $("#txtVariantListAC").autocomplete({
            // Source is generated by the AJAX call.
            source: function (request, response) {
                var hdnProductId = $('.hidden-product').val();
                $.ajax({
                    url: wsurl + '/api/Store/GetBasicProduct/' + hdnProductId,
                    dataType: "json",
                    data: {
                        term: request.term,
                    },
                    type: "GET",
                    contentType: "application/json; charset=utf-8",
                    success: function (data) {
                        // Map function for pushing data to the master list for filtering.
                        var array = $.map(data.ProductVariants, function (item) {
                            return {
                                label: item.Name,
                                val: item.VariantID
                            }
                        });
                        // Filter the response data.
                        response($.ui.autocomplete.filter(array, request.term));
                    },
                // TODO: Make Kerry decide on how to handle services being down.
                    error: function (response) {
                        console.log('Error in variant name.');
                    },
                    failure: function (response) {
                        console.log('Failure in variant name.);
                    }
                });
            },
            minLength: 1,
            delay: 1000,
            select: function (e, i) {
                // Update the boxes.
                $("input.hidden-variant").attr('value', i.item.val);
                $("#hdnVariantName").val(i.item.label);
            },
        });

    }

</script>

<asp:HiddenField ID="hdnProductName" ClientIDMode="Static" runat="server" />
<asp:HiddenField ID="hdnVariantName" ClientIDMode="Static" runat="server" />

<div><label for="txtProductListAC"><strong>Product Name</strong></label></div>
<div><asp:TextBox runat="server" ID="txtProductListAC" ClientIDMode="Static" autocomplete="on" CssClass="form-control" /></div>
<br />
<div><label for="txtVariantListAC"><strong>Variant Name</strong></label></div>
<div><asp:TextBox runat="server" ID="txtVariantListAC" ClientIDMode="Static" autocomplete="on" CssClass="form-control" /></div>
