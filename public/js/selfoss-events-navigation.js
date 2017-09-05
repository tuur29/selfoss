/**
 * initialize navigation events
 */
selfoss.events.navigation = function() {

    // init colorpicker
    $(".color").spectrum({
        showPaletteOnly: true,
        color: 'blanchedalmond',
        palette: [
            ['#ffccc9', '#ffce93', '#fffc9e', '#ffffc7', '#9aff99', '#96fffb', '#cdffff' , '#cbcefb', '#fffe65', '#cfcfcf', '#fd6864', '#fe996b','#fcff2f', '#67fd9a', '#38fff8', '#68fdff', '#9698ed', '#c0c0c0', '#fe0000', '#f8a102', '#ffcc67', '#f8ff00', '#34ff34', '#68cbd0', '#34cdf9', '#6665cd', '#9b9b9b', '#cb0000', '#f56b00', '#ffcb2f', '#ffc702', '#32cb00', '#00d2cb', '#3166ff', '#6434fc', '#656565', '#9a0000', '#ce6301', '#cd9934', '#999903', '#009901', '#329a9d', '#3531ff', '#6200c9', '#343434', '#680100', '#963400', '#986536', '#646809', '#036400', '#34696d', '#00009b', '#303498', '#000000', '#330001', '#643403', '#663234', '#343300', '#013300', '#003532', '#010066', '#340096']
        ],
        change: function(color) {
            $(this).css('backgroundColor', color.toHexString());
            
            $.ajax({
                url: $('base').attr('href') + 'tags/color',
                type: 'POST',
                data: {
                    tag: $(this).parent().find('.tag').html(),
                    color: color.toHexString()
                },
                success: function() {
                    selfoss.dbOnline.reloadList();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    selfoss.ui.showError('Can not save new color: '+
                                         textStatus+' '+errorThrown);
                }
            });
            
        }
    });
    
    // filter
    $('#nav-filter > li').unbind('click').click(function () {
        if($(this).hasClass('nav-filter-newest'))
            selfoss.filter.type='newest';
        else if($(this).hasClass('nav-filter-unread'))
            selfoss.filter.type='unread';
        else if($(this).hasClass('nav-filter-starred'))
            selfoss.filter.type='starred';

        selfoss.events.reloadSamePath = true;
        if (selfoss.events.lastSubsection == null)
            selfoss.events.lastSubsection = 'all';
        selfoss.events.setHash(selfoss.filter.type, 'same');

        $('#nav-filter > li').removeClass('active');
        $(this).addClass('active');
        
        selfoss.ui.hideMobileNav();
    });
    
    // hide/show filters
    $('#nav-filter-title').unbind('click').click(function () {
        $('#nav-filter').slideToggle( "slow", function() {
            selfoss.events.resize();
        });
        $('#nav-filter-title').toggleClass("nav-filter-collapsed nav-filter-expanded");
        $('#nav-filter-title').attr('aria-expanded', function (i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });
    
    // tag
    $('#nav-tags > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');
        
        if($(this).hasClass('nav-tags-all')==false) {
            selfoss.events.setHash(selfoss.filter.type,
                                   'tag-' + $(this).find('span').html());
        } else {
            selfoss.events.setHash(selfoss.filter.type, 'all');
        }
            
        selfoss.ui.hideMobileNav();
    });
    
    // hide/show tags
    $('#nav-tags-title').unbind('click').click(function () {
        $('#nav-tags').slideToggle("slow");
        $('#nav-tags-title').toggleClass("nav-tags-collapsed nav-tags-expanded");
        $('#nav-tags-title').attr('aria-expanded', function (i, attr) {
            return attr == 'true' ? 'false' : 'true';
        });
    });
    
    // source
    $('#nav-sources > li').unbind('click').click(function () {
        $('#nav-tags > li').removeClass('active');
        $('#nav-sources > li').removeClass('active');
        $(this).addClass('active');

        selfoss.events.setHash(selfoss.filter.type,
                               'source-' + $(this).attr('id').substr(6));
        
        selfoss.ui.hideMobileNav();
    });
    
    // hide/show sources
    $('#nav-sources-title').unbind('click').click(function () {
        var toggle = function () {
            $('#nav-sources').slideToggle("slow");
            $('#nav-sources-title').toggleClass("nav-sources-collapsed nav-sources-expanded");
            $('#nav-sources-title').attr('aria-expanded', function (i, attr) {
                return attr == 'true' ? 'false' : 'true';
            });
        }

        selfoss.filter.sourcesNav = $('#nav-sources-title').hasClass("nav-sources-collapsed");
        if( selfoss.filter.sourcesNav && !selfoss.sourcesNavLoaded ) {
            $.ajax({
                url: $('base').attr('href') + 'sources/sourcesStats',
                type: 'GET',
                success: function(data) {
                    selfoss.refreshSources(data.sources);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    selfoss.ui.showError('Can not load nav stats: '+
                                         textStatus+' '+errorThrown);
                }
            });
        }else{
            toggle();
        }
    });

    // emulate clicking when using keyboard
    $('#nav-filter > li, #nav-tags > li, #nav-tags-title, #nav-sources > li, #nav-sources-title').unbind('keypress').keypress(function (e) {
        if(e.keyCode === 13) {
            $(this).click();
        }
    });

    // show hide navigation for mobile version
    $('#nav-mobile-settings').unbind('click').click(function () {
        var nav = $('#nav');
        
        // show
        if(nav.is(':visible')==false) {
            nav.slideDown(400, function() {
                $(window).scrollTop(0);
            });
            
        // hide
        } else {
            nav.slideUp(400, function() {
                $(window).scrollTop(0);
            });
        }
        
    });

    // updates sources
    $('#nav-refresh').unbind('click').click(function () {
        $('#nav-refresh').addClass('loading');

        $.ajax({
            url: $('base').attr('href') + 'update',
            type: 'GET',
            dataType: 'text',
            data: {},
            success: function(response) {
                // hide nav on smartphone
                if(selfoss.isSmartphone())
                    $('#nav-mobile-settings').click();

                // probe stats and prompt reload to the user
                selfoss.dbOnline.sync(true).done(function() {
                    var refreshed = 'Sources have been refreshed';
                    if( $('.unread-count').hasClass('unread') ) {
                        selfoss.ui.showMessage(refreshed, 'Reload list',
                                               function() {
                            $('#nav-filter-unread').click();
                        });
                    }
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.ui.showError('Cannot refresh sources: ' + errorThrown);
            },
            complete: function(data) {
                $('#nav-refresh').removeClass('loading');
            }
        });
    });
    
    // login
    $('#nav-login').unbind('click').click(function () {
        selfoss.events.setHash('login', false);
    });
    
    // only loggedin users
    if($('body').hasClass('loggedin')==true) {
        $('#nav-mark').unbind('click').click(selfoss.markVisibleRead);
        
        // show sources
        $('#nav-settings').unbind('click').click(function () {
            selfoss.events.setHash('sources', false);
            
            if(selfoss.isSmartphone())
                $('#nav-mobile-settings').click();
        });
        
        
        // logout
        $('#nav-logout').unbind('click').click(selfoss.logout);
        
        // logo click reload
        $('#nav-logo').unbind('click').click(function(){ window.location.hash = "#unread/all"; });
    }
};
