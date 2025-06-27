/**
 * AvatarMaker 3.x By InochiTeam
 *
 * @updated       29/10/2022 (3.3.1)
 * @dependencies  Jquery 3.6.1
 */

"use strict";

(function( $ ) {

    $.fn.avatarMaker = function( backUrl, avatarTree ) {

        var options = {

            backUrl: backUrl,

            cacheSizes: { },

            previewsSizes: { }

        };

        var cache =  { loaded: 0, items: {}, step:0 };
        var canvas = { layers:{}, avatar:{ preview:{}, buffer:{} } };
        var data =   { layers:{}, palettes:{}, featuredItems: {} };
        var ui = { activeLayer:false, activePalette: false };

        /* This data structure represents the currently previewed avatar and will be sent to backend for the final rendering */
        var avatar = { "size":1024, "layers":{}, "colors":{} };


        /**
         * If the browser doesn't support canvas there is no purpose in loading and trying to run the application.
         * The app is stopped and a proper error is shown to the user
         */
        if( isCanvasSupported() )
            appInit();


        /**
         * Layers tabs click event
         */
        $(".layers-menu ul li").on("click",function(){

            ui.activeLayer = $(this).attr('avm-layerId');
            ui.activePalette = data.layers[ui.activeLayer].colors;

            /* Change the active tab */
            $(".layers-menu ul .active").removeClass('active');
            $(".layers-tabs .active").removeClass('active');
            $(".palettes-tabs").hide();
            $(".palettes-tabs li.active").removeClass('active');

            $(this).addClass('active');
            $('.layers-tabs li[avm-layerId="'+ui.activeLayer+'"]').addClass('active');

            /* If the selected layer accepts coloring display the proper palette */
            if(ui.activePalette)
            {

                if( options.allowCustomColors )
                {
                    $("#picker-place").spectrum("set", $(this).attr("avm-color"));
                    $(".picker-button").css( "background", avatar.colors[data.layers[ui.activeLayer].colors] );
                }

                $(".palettes-tabs").show();
                $('.palettes-tabs li[avm-paletteId="'+ui.activePalette+'"]').addClass('active');
                
                uiPalettesTabs();
            }

        });


        /**
         * Manage colors palettes scrolling
         */
        function uiPalettesTabs()
        {
            var arrowTop = $('.palettes-tabs .arrow-top');
            var arrowBottom = $('.palettes-tabs .arrow-bottom');
            var pagination = $('.tabs-pagination');
            var palette = $('.palettes-tabs .tabs-content li[avm-paletteId="'+ data.layers[ui.activeLayer].colors +'"] ul');

            /* Restore the initial status of the palette  and pagination */
            arrowTop.removeClass("arrow-active");
            arrowBottom.removeClass("arrow-active");
            arrowTop.off('click');
            arrowBottom.off('click');
            palette.off('scroll');
            palette.scrollTop(0);

            /* If the colors overflow the available space activate the arrows and bind the click events */
            if (  palette.outerHeight() < palette[0].scrollHeight )
            {

                arrowBottom.addClass("arrow-active");
                arrowBottom.click( arrowScrollDown );
                arrowTop.click( arrowScrollUp );

                /* Generate the pagination for the current tab */
                compilePagination();

            }
            else
            {
                /* Clear unnecessary pagination */
                pagination.html('');
            }


            /**
             * Add dots to the pagination and register a watcher
             */
            function compilePagination()
            {
                pagination.html('');

                for(var i = 0; i <= ( palette[0].scrollHeight - palette.outerHeight() ) / palette.height() ; i++ )
                {
                    pagination.append('<li></li>');
                }

                pagination.find("li:first-child").addClass("pagination-active");

                palette.scroll(function() {

                    var step = Math.floor(palette.scrollTop() / palette.height());

                    pagination.find("li").removeClass("pagination-active");
                    pagination.find("li:nth-child(" + (step + 1 ) + ")").addClass("pagination-active");

                });

            }



            /**
             * Arrow down click event
             */
            function arrowScrollDown()
            {
                arrowTop.addClass("arrow-active");

                palette.scrollTop( palette.scrollTop() +  palette.height() );

                if( palette.scrollTop() == (palette[0].scrollHeight - palette.outerHeight()) && (palette[0].scrollHeight - palette.outerHeight()) != 0 )
                arrowBottom.removeClass("arrow-active");
            }



            /**
             * Arrow up click event
             */
            function arrowScrollUp()
            {
                arrowBottom.addClass("arrow-active");

                palette.scrollTop( palette.scrollTop() - palette.height() );

                if( palette.scrollTop() == 0  && (palette[0].scrollHeight - palette.outerHeight()) != 0 )
                arrowTop.removeClass("arrow-active");
            }

        }

        /**
         * Random button click event
         */
        $("#btn_random").on("click",function(){

            /* Generate a random avatar structure */
            avatarRandom();

            /* Update the preview */
            renderPreview();
        });


        /**
         * Download button click event
         */
        $("#btn_download").on("click",function(){

            var urlString = "?avm_forcewn=true&size=1024&avm_items=";

            $.each(avatar.layers, function(id, index){
                urlString += id + ":" + data.layers[id].items[index] + "|";
            });

            urlString += "&avm_colors=";

            $.each(avatar.colors, function(id, color){
                urlString += id + ":" + color + "|";
            });

            urlString = urlString.replace(/#/g, "0x");


            if( options.outputFormat !== "saved" )
            {
                window.open( options.backUrl + urlString );
            }
            else
            {
                $( '.panel-download' ).slideDown();

                $.ajax({
                    url: options.backUrl + urlString
                })
                .done(function( data ) {
                    $( '.panel-download' ).slideUp();
                });

            }


        });


        /**
         * Display the error overlay
         *
         * @param {string} info The text to display in the error message
         */
        function displayError( info )
        {
            $( ".panel-error" ).show();
            $( ".panel-error .error-details" ).html( info );
        }


        /**
         * Resize the ui based on viewport sizes
         * This function assists the media queries when a css only solution is not viable
         */
        function appAdapt()
        {

            /* Set the UI in compact mode if requested */
            if( options.compactUi )
            {
                $(".items-tabs").addClass("compactUi");
                $(".colors-list").addClass("compactUi");
            }
        }


        /**
		 * Initialize the app by requesting the configuration to the backend
         */
		function appInit()
		{
          

            /* Load a reference to the preview canvas and its context */
            canvas.avatar.preview.elm = document.getElementById("previewBox");
            canvas.avatar.preview.ctx = canvas.avatar.preview.elm.getContext("2d");


            /* Fetch configuration file from the backend */
            $.getJSON( options.backUrl )
				/* If something goes wrong, display an error and lock the app */
                .fail(function( jqxhr, textStatus, error ) {
                    displayError( jqxhr.responseText );
                })
                .done(function( configJson ) {

                    // Compute the width in percentage of each progress bar step
                    cache.barStep = 100 / ( Object.keys(configJson.layers).length + 1 );

                    /* update the progress bar after receiving the configuration */
                    $(".loading-progress .progress-bar").css( "width", ++cache.step*cache.barStep + "%" );

                    /* Hide inochiTeam Branding if set in the configuration*/
                    if( !configJson.app.displayCredits )
                        $( ".side-branding" ).hide();


                    /* Import parameters from the configuration*/
                    options.randStartup = configJson.app.randStartup;
                    options.randUpdateColors = configJson.app.randUpdateColors;
                    options.outputFormat = configJson.app.outputFormat;
                    options.compactUi = configJson.app.compactUi;
                    options.cacheSizes = configJson.app.cacheSizes;
                    options.allowCustomColors = configJson.app.allowCustomColors;

                    options.previewsSizes.x = 100;
                    options.previewsSizes.y = Math.round(options.cacheSizes.y * options.previewsSizes.x / options.cacheSizes.x);


                    /* Set the right size for the canvas */
                    canvas.avatar.preview.elm.width = options.cacheSizes.x;
                    canvas.avatar.preview.elm.height = options.cacheSizes.y;


                    /* Generate an hidden canvas to use as a buffer for the preview */
                    canvas.avatar.buffer = canvasGenerate(options.cacheSizes.x, options.cacheSizes.y);


                    /* Store the received data for later */
                    data.layers = configJson.layers;
                    data.palettes = configJson.palettes;
                    data.featuredItems = configJson.featuredItems;
                    

                    /* Disable the background if requested */
                    options.transparentBackground = configJson.app.transparentBackground;

                    if( options.transparentBackground )
                    {
                        delete data.layers.background;
                        $(".layers-menu ul li[avm-layerId='background'").hide();
                    }


                    /* Set your brand name in the interface */
                    $( '#app_brand' ).html( configJson.app.brand );
                    $(document).prop('title', configJson.app.brand + ' | avatarMaker');

                    /* Localize the application interface */
                    appInitLocal( configJson.local );

                    /* Cache all the images that will be used in the canvasses
                     * If you try to draw an image that has not already been loaded by the browser the canvas won't return
                     * an error but nothing will be shown. A pretty bad behaviour if you ask me... */
                    appInitCache( function(){

                        /* Populate the app with the received data */
                        appInitLayersTabs();
                        appInitPalettesTabs();

                        /* Resize part of the ui based on the viewport size ad register and start listening for resize events */
                        appAdapt();

                        /* If set in the configuration generate a random avatar */
                        if( options.randStartup )
                            avatarRandom();

                        /* */
                        if( avatarTree !== undefined)
                            avatarLoadTree( avatarTree );

                        /* Render the avatar preview for the first time */
                        renderPreview();

                        
                        if(data.layers[ui.activeLayer].colors)
                            uiPalettesTabs();

                        $( window ).resize(function() {
                            if(data.layers[ui.activeLayer].colors)
                                uiPalettesTabs();

                        });

                        /* The initialization of the app is finished we can hide the loading screen */
                        $( ".panel-loading" ).fadeOut();
                    });

                });
		}


        /**
		 * Populate the interface with the localization data
         */
        function appInitLocal( local )
		{
            $.each( local, function( id, value ){
                $( '[avm-local="'+id+'"]' ).html( value );
            });
        }


        /**
         * Preload all the images that will be used in a canvas
         */
        function appInitCache( callback )
        {
            /* Load all images in parallel to save some time
             * To be precise we try to download all images at the same time. The real level of parallelism is unpredictable
             * as browsers will open only a certain number of parallel connection to a single host (usually 4-6).
             * Tentar non nuoce (There's no harm in trying). */
            $.each( data.layers, function( img, NaN ){

                var res = new Image();
                res.onload = resLoaded;
                res.src = "cache/" + img + ".png";
                cache.items[img] = res;

            });

            /* Each time an image is loaded update the progress bar */
            function resLoaded()
            {
                cache.loaded++;

                $(".loading-progress .progress-bar").css( "width", ++cache.step*cache.barStep + "%" );

                if (cache.loaded >= Object.keys(data.layers).length) {
                    callback();
                }
            }
        }

        /**
         * Checks if the given item is featured in that layer
         */
        function isItemFeatured(layer, item) {
            return data.featuredItems[layer]?.indexOf(item) > -1
        }

        /**
         * Initialize the layers tabs
         */
        function appInitLayersTabs( )
        {
            /* Populate layers and items */
            $.each(data.layers, function(id, layer) {

                /* Add a tab for the current layer */
                $(".layers-tabs").append('<li avm-layerId="' + id + '"><ul class="items-tabs"></ul></li>');

                /* Set a default item for current layers inside the avatar tree */
                avatar.layers[id] = 0;

                /* If the layer doesn't need to be colored, we can avoid creating a canvas for it */
                var dataURL = 'cache/' + id + '.png';
                if( layer.colors )
                {
                    /* Initialize the avatar colors with the first one in the palette */
                    avatar.colors[layer.colors] = data.palettes[layer.colors][0];
                    /* Create an hidden canvas to use as a buffer for coloring the current layer */
                    canvas.layers[id] = canvasGenerate( options.previewsSizes.x*layer.items.length, options.previewsSizes.y );

                    /* Tint the current layer */
                    var dataURL = canvasTintItems( id, layer );
                }


                /* Add all the items into the layer tab */
                $.each(layer.items, function(x, item) {

                    $('.layers-tabs li[avm-layerId="'+id+'"] .items-tabs').append('<li avm-itemId="' + x + '" ' + (isItemFeatured(id, item) ? 'class="featured"' : '') + '><div class="item-preview" style="background-image: url('+dataURL+'); background-position:-'+x*options.previewsSizes.x+'px 0;"/></div></li>');

                });

            });

            //$('.layers-tabs li').css('width', options.previewsSizes.x + "px");
            $('.layers-tabs li .item-preview').css('width', options.previewsSizes.x + "px");
            $('.layers-tabs li .item-preview').css('height', options.previewsSizes.y + "px");
            $('.layers-tabs li .item-preview').css('background-size', "auto " + options.previewsSizes.y + "px");

            /* Set the first layer as active */
            var activeLayer = $('.layers-menu li.active');

            if(activeLayer.length) {
                ui.activeLayer = activeLayer.attr("avm-layerId");
            } else {
                ui.activeLayer = $('.layers-menu li').first().attr("avm-layerId");
            }

            $('.layers-menu li[avm-layerId="' + ui.activeLayer + '"]').addClass('active');
            $('.layers-tabs li[avm-layerId="' + ui.activeLayer + '"]').addClass('active');

            /* Listen for click event on the items in each layer */
            $(".items-tabs li").on("click",function(){
                avatar.layers[ui.activeLayer] = $(this).attr("avm-itemId");

                /* Update the preview */
                renderPreview();
            });
        }


        /**
         * Initialize the colors palettes
         */
        function appInitPalettesTabs()
        {
            if(data.layers[ui.activeLayer].colors)
                ui.activePalette = data.layers[ui.activeLayer].colors;
            

            $.each(data.palettes, function(id, colors) {

                /* Create a tab for the current palette */
                $(".palettes-tabs .tabs-content").append('<li avm-paletteId="' + id + '"><ul class="colors-list"></ul></li>');

                /* Set the first color of the palette in the avatar structure */
                avatar.colors[id] = data.palettes[id][0];

                /* Add each color to the current palette tab */
                $.each(colors, function( k, c ) {
                    $('.palettes-tabs .tabs-content li[avm-paletteId="'+id+'"] .colors-list').append('<li avm-color="' + c + '" style="background:' + c + '"></li>');
                });

            });


            /* Initialize color picker */
            if( options.allowCustomColors )
            {
                $(".palettes-picker").show();

                $("#picker-place").spectrum({

                     flat: true,
                     showButtons: false,

                     move: function( color ) {

                         avatar.colors[ui.activePalette] = color.toHexString();
                         appUpdateColors(ui.activePalette);
                         renderPreview();

                    }

                });


                $(".palettes-picker .picker-button").on("click",function(){

                    $(".palettes-picker .picker-modal").toggle();

                });

            }


            /* Listen for the click event on all colors */
            $(".colors-list li").on("click",function(){


                if( options.allowCustomColors )
                {
                    $("#picker-place").spectrum("set", $(this).attr("avm-color"));
                    $(".picker-button").css( "background", avatar.colors[data.layers[ui.activeLayer].colors] );
                }

                var colorId = $( this ).parent().parent().attr("avm-paletteId");
                avatar.colors[colorId] = $(this).attr("avm-color");
                appUpdateColors(colorId);
                renderPreview();
            });

            /* If the active layer has a palette associated to it, display it */
            if(ui.activePalette)
                $('.palettes-tabs li[avm-paletteId="' + ui.activePalette + '"]').addClass('active');
            else
                $(".palettes-tabs").hide();

        }


        /**
         * Update the previews of all the layers that share a palette
         */
        function appUpdateColors( changedColor )
        {
            $(".picker-button").css("background", avatar.colors[changedColor]);

            $.each( data.layers, function( index, layer ){

                /* Skip the layer if it doesn't use the changed palette */
                if(layer.colors !== changedColor)
                    return true;

                /* Tint the current layer */
                var dataURL =  canvasTintItems( index, layer );

                /* Update the current layer */
                $('.layers-tabs li[avm-layerId="'+index+'"] .items-tabs li .item-preview').css("background-image", "url(" + dataURL + ")");
            });
        }


        /**
         * Generate a random avatar structure
         */
        function avatarRandom()
        {

            /* Select a random item for each layer */
            $.each(avatar.layers, function(id, name) {
                avatar.layers[id] = Math.floor(Math.random()*data.layers[id].items.length);
            });

            /* Select a random color for reach palette */
            $.each(avatar.colors, function(id, name) {
                var oldColor = avatar.colors[id];
                avatar.colors[id] = data.palettes[id][ Math.floor(Math.random()*data.palettes[id].length)];

                /* If the random color is the same as the old one skip the color update. This somewhat speeds up the preview but that this is just occasional.
                 * All layers are skipped if the user has disabled the color update on random avatars in the settings */
                if( options.randUpdateColors && avatar.colors[id] !== oldColor )
                    appUpdateColors( id );

            });

            if( options.allowCustomColors )
                {
                    $("#picker-place").spectrum("set", avatar.colors[data.layers[ui.activeLayer].colors] );
                    $(".picker-button").css( "background", avatar.colors[data.layers[ui.activeLayer].colors] );
                }

        }



        /**
         * Load an avatar into the ui from a json tree
         */
        function avatarLoadTree( avatarTree )
        {
            if(typeof(avatarTree) !== 'object')

            /* Try loading the json into the ui, if it's invalid ignore it */
            try
            {
                avatarTree = JSON.parse(avatarTree);

                console.log("[AvatarMaker] Avatar tree correctly loaded");
            }
            catch(e)
            {
                console.error("[AvatarMaker] Ignoring invalid avatar tree");
                return false;
            }


            /* Load all colors */
            $.each(avatarTree.colors, function(id, name) {
                var oldColor = avatar.colors[id];

                avatar.colors[id] = name;

                if( options.randUpdateColors && avatar.colors[id] !== oldColor )
                    appUpdateColors( id );

            });

            /* Load all items */
            $.each(avatarTree.layers, function(id, name) {

                if( data.layers[id] === undefined )
                {
                    console.error("[AvatarMaker] Ignoring non existent layer in avatar tree");
                    return true;
                }


                var itemId = data.layers[id].items.indexOf( name );

                if( itemId == -1 )
                {
                    console.error("[AvatarMaker] Ignoring non existent layer in avatar tree");
                    return true;
                }

                avatar.layers[id] = itemId;

            });

        }



        /**
         * Render a preview of the avatar structure
         */
        function renderPreview( )
        {

            /* Clear the preview canvas */
            canvas.avatar.preview.ctx.clearRect(0, 0, options.cacheSizes.x, options.cacheSizes.y);

            canvas.avatar.preview.ctx.imageSmoothingQuality = "high";

            $.each(avatar.layers, function(id, index) {

                /* Draw an item into the canvas tinting it if needed */
                if(data.layers[id].colors)
                {
                    canvas.avatar.buffer.ctx.fillStyle = avatar.colors[data.layers[id].colors];
                    canvas.avatar.buffer.ctx.fillRect(0, 0, options.cacheSizes.x, options.cacheSizes.y);

                    canvas.avatar.buffer.ctx.globalCompositeOperation = 'destination-in';

                    canvas.avatar.buffer.ctx.drawImage(cache.items[id], options.cacheSizes.x * index, 0, options.cacheSizes.x, options.cacheSizes.y, 0, 0, options.cacheSizes.x, options.cacheSizes.y);


                    canvas.avatar.buffer.ctx.globalCompositeOperation = 'multiply';
                    canvas.avatar.buffer.ctx.drawImage(cache.items[id], options.cacheSizes.x * index, 0, options.cacheSizes.x, options.cacheSizes.y, 0, 0, options.cacheSizes.x, options.cacheSizes.y);

                    canvas.avatar.preview.ctx.drawImage(canvas.avatar.buffer.elm, 0, 0, options.cacheSizes.x, options.cacheSizes.y);
                    canvas.avatar.buffer.ctx.clearRect(0, 0, options.cacheSizes.x, options.cacheSizes.y);
                }
                else
                    canvas.avatar.preview.ctx.drawImage(cache.items[id], options.cacheSizes.x * index, 0, options.cacheSizes.x, options.cacheSizes.y, 0, 0, options.cacheSizes.x, options.cacheSizes.y);

            });

        }


        /**
         * Test if the browser supports canvas or not
         */
        function isCanvasSupported(){

            var elem = document.createElement('canvas');

            if( !(elem.getContext && elem.getContext('2d')) )
            {
                displayError("The browser doesn't support canvas");
                return false;
            } else
            {
                return true;
            }

        }


        /**
         * Generate an hidden canvas without adding it to the DOM
         */
        function canvasGenerate( width, height )
        {
            var canvas = {};

            canvas.elm = document.createElement('canvas');
            canvas.elm.width = width;
            canvas.elm.height = height;
            canvas.ctx = canvas.elm.getContext("2d");

            return canvas;
        }


        /**
         * Tint the preview for a layer
         */
        function canvasTintItems( index, layer )
        {
            /* Erase the canvas */
            canvas.layers[index].ctx.clearRect(0, 0, canvas.layers[index].elm.width, canvas.layers[index].elm.height);

            canvas.layers[index].ctx.imageSmoothingQuality = "high";

            /* If the item needs to be tinted dot it or just skip it. This function is used to return the dataURL this is why it can *optionally* tint */
            if(layer.colors) {
                canvas.layers[index].ctx.drawImage(cache.items[index], 0, 0, options.previewsSizes.x * layer.items.length, options.previewsSizes.y);
                canvas.layers[index].ctx.globalCompositeOperation = 'source-atop';

                canvas.layers[index].ctx.fillStyle = avatar.colors[layer.colors];
                canvas.layers[index].ctx.fillRect(0, 0, options.previewsSizes.x * layer.items.length, options.previewsSizes.y);

                canvas.layers[index].ctx.globalCompositeOperation = 'multiply';
            }
            canvas.layers[index].ctx.drawImage(cache.items[index], 0, 0, options.previewsSizes.x * layer.items.length, options.previewsSizes.y);


            /* Return the dataURL */
           return canvas.layers[index].elm.toDataURL();
        }
    };

}( jQuery ));
