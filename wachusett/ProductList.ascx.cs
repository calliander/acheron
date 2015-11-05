using CMS.Helpers;
using CMS.PortalControls;
using System;
using System.Collections.Generic;
using System.Data;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Web;
using System.Web.Helpers;
using System.Web.Mvc;
using System.Web.Script.Services;
using System.Web.Services;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

public partial class Wachusett_CMSWebpart_ProductList : CMSAbstractWebPart
{

    #region "Methods"

    /// <summary>
    /// Page load event handler.
    /// </summary>
    protected void Page_Load(object sender, EventArgs e)
    {

        // Add CSS/JS needed.
        Services.IncludeCSS(this.Page, "/App_Themes/Wachusett2015/add2cart.css");
        Services.IncludeJS(this.Page, "/App_Themes/Wachusett2015/add2cart.js");

        // Fix the product quantity box.
        ProductQuantity.Attributes["type"] = "number";

        // Default values.
        string prodJson = "";
        string vrntJson = "";

        // Get the product information.
        int productId = Convert.ToInt32(DataHelper.GetNotEmpty(GetValue("ProductID"), "0"));
        using (WebClient wc = new WebClient())
        {
            // Load the JSON.
            prodJson = wc.DownloadString("http://dc-remote2008r2.cloudapp.net:8787/api/Store/GetBasicProduct/" + productId);
            wc.Dispose();
        }
        // Turn into a dynamic JSON object.
        var jProd = Json.Decode(prodJson);
        ProductId.Value = Convert.ToString(jProd.ProductID);
        ProductName.Value = jProd.Name;

        // Get the variant information.
        int variantId = Convert.ToInt32(DataHelper.GetNotEmpty(GetValue("VariantID"), "0"));
        using (WebClient wc = new WebClient())
        {
            // Load the JSON.
            vrntJson = wc.DownloadString("http://dc-remote2008r2.cloudapp.net:8787/api/Store/GetBasicVariantsByProductId/" + productId);
            wc.Dispose();
        }
        // Turn into a dynamic JSON object.
        var jVrnt = Json.Decode(vrntJson);

        // Process the JSON object.
        foreach (var vItem in jVrnt)
        {
            // Locate the matching variant ID>
            if (vItem.VariantID == variantId)
            {
                // Set the hidden field values.
                VariantId.Value = variantId.ToString();
                ProductVariant.Value = vItem.Name;
                ProductPrice.Value = Convert.ToString(vItem.Price);
                ProductSKU.Value = vItem.SKUSuffix;
            }
        }

    }

    /// <summary>
    /// Add to cart button click handler.
    /// </summary>
    public void AddToCart_Click(object sender, EventArgs e)
    {

        // Get values.
        string ThisCustID = Services.GetCustID();
        string ThisProdName = ProductName.Value;
        string ThisProdVar = ProductVariant.Value;
        string ThisProdSKU = ProductSKU.Value;
        string ThisProdPrice = ProductPrice.Value;
        string ThisProdQty = ProductQuantity.Value;
        string ThisProductId = ProductId.Value;
        string ThisVariantId = VariantId.Value;

        // Add to cart.
        object a2cResult = Services.AddToCart(ThisCustID, ThisProductId,ThisVariantId, ThisProdVar,  ThisProdSKU, ThisProdPrice, ThisProdQty);

        // post cart logic removed for business security reasons

    }

    #endregion

}
