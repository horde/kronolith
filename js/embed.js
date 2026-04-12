window.addEventListener('load', function() {
    var nodeCount = kronolithNodes.length;
    for (var n = 0; n < nodeCount; n++) {
        var j = kronolithNodes[n];
        document.getElementById(j).innerHTML = kronolith[j];
    }
});
