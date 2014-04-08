
var $j = jQuery.noConflict();

$j(function(){
    
    // --- expand/collapse ---
    $j('.somc-subpages .somc-subpages-expand').click(function(){
        if ($j(this).next().css('display') == 'none'){
            
            $j(this).next().show('blind', null, 'fast');
            
            $j(this).removeClass('fa-plus-square')
                    .addClass('fa-minus-square');
            
        } else {
            
            $j(this).next().hide('blind', null, 'fast');
            
            $j(this).removeClass('fa-minus-square')
                    .addClass('fa-plus-square');
            
        }
        
        event.preventDefault();
        return false;
    });
    
    // --- sorting ---
    $j('.somc-subpages .somc-subpages-sort').click(function(){
    
        var parentContainer = $j(this).parent();
        var postId = $j(this).attr('data-postId');
        var titleContainers = [];
        var sortAsc = true;
        
        // --- discover sorting direction ---
        if ($j(this).attr('data-sort') === 'asc'){
            // --- descending sort ---
            $j(this).attr('data-sort','desc')
                    .removeClass('fa-sort-alpha-desc')
                    .addClass('fa-sort-alpha-asc');
            
            sortAsc = false;
            
        } else {
            // --- ascending sort ---
            $j(this).attr('data-sort','asc')                    
                    .removeClass('fa-sort-alpha-asc')
                    .addClass('fa-sort-alpha-desc');
            
            sortAsc = true;
            
        }
        
        parentContainer.animate({
            opacity: 0
        }, 'fast', null, function(){
            
            // --- find all titles of subpages given parent page ---
            parentContainer.find("[data-postParentId='"+ postId +"']").each(function(index, element){
                titleContainers.push({
                    title: $j(element).html(),
                    container: $j(element).parent().parent().detach()
                });
            });

            var separators = parentContainer.children("ul").find(".somc-subpages-separator").detach();

            // --- sorting map function ---
            titleContainers = titleContainers.sort(function(a, b){
                var tempBag = [a.title, b.title].sort();
                return (!sortAsc && tempBag[0] === a.title) || (sortAsc && tempBag[1] === a.title);
            });

            // --- rearrange html elements ---
            for (var i = 0; i < titleContainers.length; i++){
                parentContainer.children("ul").append(titleContainers[i].container);
                if (i < separators.length)
                    parentContainer.children("ul").append(separators[i]);
            }

            parentContainer.animate({
                opacity: 1
            }, 'fast');
            
        });
        

    
        event.preventDefault();
        return false;
    
    });
    
});