<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>Products XML Report - Amah Mary's Kitchen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #fdf6f0; color: #333; padding: 30px; }
        h1 { color: #d4451a; text-align: center; margin-bottom: 8px; }
        p.subtitle { text-align: center; color: #888; margin-bottom: 24px; font-size: 0.9rem; }
        table { width: 100%; max-width: 1000px; margin: 0 auto; border-collapse: collapse; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        th { background: #d4451a; color: #fff; padding: 12px 14px; text-align: left; font-size: 0.9rem; }
        td { padding: 10px 14px; border-bottom: 1px solid #f0e0d6; }
        tr:hover { background: #fef5f0; }
        .status-active { color: #28a745; font-weight: 600; }
        .status-low { color: #ffc107; font-weight: 600; }
        .status-disc { color: #dc3545; font-weight: 600; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #d4451a; text-decoration: none; }
    </style>
</head>
<body>
    <h1>🍳 Amah Mary's Kitchen — Product Catalog (XML)</h1>
    <p class="subtitle">Data sourced from products.xml | Transformed with products.xsl</p>
    <table>
        <tr>
            <th>ID</th>
            <th>Product Name</th>
            <th>Description</th>
            <th>Price (₱)</th>
            <th>Stock</th>
            <th>Status</th>
        </tr>
        <xsl:for-each select="products/product">
        <xsl:sort select="name"/>
        <tr>
            <td><xsl:value-of select="@id"/></td>
            <td><strong><xsl:value-of select="name"/></strong></td>
            <td><xsl:value-of select="description"/></td>
            <td>₱<xsl:value-of select="price"/></td>
            <td><xsl:value-of select="stock"/></td>
            <td>
                <xsl:choose>
                    <xsl:when test="status='Active'">
                        <span class="status-active"><xsl:value-of select="status"/></span>
                    </xsl:when>
                    <xsl:when test="status='Low Stock'">
                        <span class="status-low"><xsl:value-of select="status"/></span>
                    </xsl:when>
                    <xsl:otherwise>
                        <span class="status-disc"><xsl:value-of select="status"/></span>
                    </xsl:otherwise>
                </xsl:choose>
            </td>
        </tr>
        </xsl:for-each>
    </table>
    <a class="back-link" href="index.php">← Back to Dashboard</a>
</body>
</html>
</xsl:template>

</xsl:stylesheet>
