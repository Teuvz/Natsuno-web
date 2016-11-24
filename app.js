(function() {
	
	var app = angular.module('gallery', []);
	
    
	app.controller('GalleryController', ['$http','$location',function($http,$location) {
	
		var Base64={_keyStr:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",encode:function(e){var t="";var n,r,i,s,o,u,a;var f=0;e=Base64._utf8_encode(e);while(f<e.length){n=e.charCodeAt(f++);r=e.charCodeAt(f++);i=e.charCodeAt(f++);s=n>>2;o=(n&3)<<4|r>>4;u=(r&15)<<2|i>>6;a=i&63;if(isNaN(r)){u=a=64}else if(isNaN(i)){a=64}t=t+this._keyStr.charAt(s)+this._keyStr.charAt(o)+this._keyStr.charAt(u)+this._keyStr.charAt(a)}return t},decode:function(e){var t="";var n,r,i;var s,o,u,a;var f=0;e=e.replace(/[^A-Za-z0-9+/=]/g,"");while(f<e.length){s=this._keyStr.indexOf(e.charAt(f++));o=this._keyStr.indexOf(e.charAt(f++));u=this._keyStr.indexOf(e.charAt(f++));a=this._keyStr.indexOf(e.charAt(f++));n=s<<2|o>>4;r=(o&15)<<4|u>>2;i=(u&3)<<6|a;t=t+String.fromCharCode(n);if(u!=64){t=t+String.fromCharCode(r)}if(a!=64){t=t+String.fromCharCode(i)}}t=Base64._utf8_decode(t);return t},_utf8_encode:function(e){e=e.replace(/rn/g,"n");var t="";for(var n=0;n<e.length;n++){var r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r)}else if(r>127&&r<2048){t+=String.fromCharCode(r>>6|192);t+=String.fromCharCode(r&63|128)}else{t+=String.fromCharCode(r>>12|224);t+=String.fromCharCode(r>>6&63|128);t+=String.fromCharCode(r&63|128)}}return t},_utf8_decode:function(e){var t="";var n=0;var r=c1=c2=0;while(n<e.length){r=e.charCodeAt(n);if(r<128){t+=String.fromCharCode(r);n++}else if(r>191&&r<224){c2=e.charCodeAt(n+1);t+=String.fromCharCode((r&31)<<6|c2&63);n+=2}else{c2=e.charCodeAt(n+1);c3=e.charCodeAt(n+2);t+=String.fromCharCode((r&15)<<12|(c2&63)<<6|c3&63);n+=3}}return t}}

		var gallery = this;
		this.api = "http://www.matthewpcharlton.com/gifs/api.php";
        this.loading = false;
		this.error = false;
        this.page = 1
        this.pages = 1;
        this.token = localStorage.getItem('token');
        this.showLogin = false;
        this.loginLoading = false;
        this.loginError = false;
        this.username = localStorage.getItem('username');
        this.password = null;
		this.files = [];
		this.selected = null;
		this.newtag = null;
		this.savingTag = false;
		this.alltags = [];
		this.newserie = null;
		this.savingSerie = false;
		this.allseries = [];
		this.selectedMode = null;
        this.filtersVisible = false;
        this.renaming = false;
		this.activeFilters = {
			tags: [{label:'panties'}],
			series: [{label:'evangelion'}]
		};
       
        this.downloadUrl = null;
        this.downloadVisible = false;
        this.downloading = false;
        this.downloadAdult = false;
        this.downloadLive = false;
        this.downloadSeries = null;
        this.downloadTags = null;
		
		this.filterAdult = 0;
	
		this.init = function() {
			
			var search = $location.search();
			console.dir( search );
			
			this.loadTags();
			this.loadSeries();
			this.load();
			
			$('body').keyup(function(e) {
				if ( e.keyCode == 39 && gallery.page < gallery.pages ) {
					gallery.load( gallery.page + 1 );
				} else if ( e.keyCode == 37 && gallery.page > 1 ) {
					gallery.load( gallery.page - 1 );
				}
			});
			
		}
		
		this.hash = function() {
			//return 'hello';
			var hash = Base64.encode( this.selected.name + this.selected.src + this.selected.adult + this.selected.details );
			return hash;
		}
		
		this.share = function( file ) {
			this.selected = file;
			alert('not coded yet');
		}
               
        this.download = function() {
            this.downloading = true;
            
            var url = this.api + '?download&token=' + this.token + '&url='+ encodeURIComponent(this.downloadUrl);
            
            if ( this.downloadAdult != false )
                url += "&adult=true";
            if ( this.downloadLive != false )
                url += "&live=true";
            if ( this.downloadSeries != null )
                url += "&series=" + encodeURIComponent(this.downloadSeries);
            if ( this.downloadTags != null )
                url += "&tags=" + encodeURIComponent(this.downloadTags);
            
            $http({
				method: 'GET',
				url: url
			}).then(function successCallback(response) {
                gallery.load( gallery.pages );
                //gallery.downloadVisible = false;
				gallery.downloading = false;
				gallery.downloadUrl = '';
			});
        }
		
        this.selectContent = function( id ) {
            $('#'+id).select();
            console.log('select '+id+' content');
        }
        		
		this.loadTags = function() {
			
			$http({
				method: 'GET',
				url: this.api + '?alltags'
			}).then(function successCallback(response) {
				gallery.alltags = response.data.tags;
			});
			
		}
		
		this.loadSeries = function() {
			
			$http({
				method: 'GET',
				url: this.api + '?allseries'
			}).then(function successCallback(response) {
				gallery.allseries = response.data.series;
			});
			
		}
	
		this.tag = function( forcedTag ) {
			
			if ( forcedTag !== undefined )
				this.newtag = forcedTag;
			
			this.error = false;
			this.savingTag = true;
			
			$http({
				method: 'GET',
				url: this.api + '?tag&id='+this.selected.name+'&value='+encodeURIComponent(this.newtag)
			}).then(function successCallback(response){
				gallery.savingTag = false;
				console.dir( gallery.selected );
				gallery.selected.tags.push(gallery.newtag);
				gallery.newtag = '';
				gallery.loadTags();
			},function errorCallback(response){
				gallery.error = true;
				gallery.savingTag = false;
			});
			
		}
	
		this.serie = function( forcedSerie ) {
			
			if ( forcedSerie !== undefined )
				this.newtserie = forcedSerie;
			
			this.error = false;
			this.savingSerie = true;
			
			$http({
				method: 'GET',
				url: this.api + '?serie&id='+this.selected.name+'&value='+encodeURIComponent(this.newserie)
			}).then(function successCallback(response){
				gallery.savingSerie = false;
				gallery.selected.series.push(gallery.newserie);
				gallery.newserie = '';
				gallery.loadSeries();
			},function errorCallback(response){
				gallery.error = true;
				gallery.savingSerie = false;
			});
			
		}
	
		this.adult = function( file ) {
			
			this.error = false;
			this.loading = true;
			this.selected = file;
			
			$http({
				method: 'GET',
				url: this.api + '?adult&id='+file.name+'&value='+!file.adult+"&token="+this.token
			}).then(function successCallback(response){
				gallery.loading = false;
				//this.load(this.page);
				gallery.selected.adult = !gallery.selected.adult;
			},function errorCallback(response){
				gallery.loadng = false;
				gallery.error = true;
			});
			
		}
		
		this.remove = function( file ) {
			
			this.loading = true;
			this.selected = file;
			
			$http({
				method: 'GET',
				url: this.api + '?remove&id='+file.name + '&token=' + this.token
			}).then(function successCalback(response){
				gallery.loading = false;
				gallery.load(gallery.page);
			}, function errorCallback(response) {
				gallery.loadng = false;
				gallery.error = true;
			});
			
		}
		
		this.logout = function() {
			this.username = null;
			this.token = null;
			localStorage.clear();
			this.load(this.page);
            this.filtersVisible = false;
		}
	
        this.login = function() {
            this.loginLoading = true;
			
            $http({
               method: 'GET',
               url: this.api + "?login&username="+this.username+"&password="+this.password,
            }).then(function successCallback(response) {
                gallery.loginLoading = false;
                gallery.token = response.data.token;
				gallery.load(gallery.page);
				localStorage.setItem('token', gallery.token);
				localStorage.setItem('username', gallery.username);
            }, function errorCallback(response){
                gallery.loginLoading = false;
                gallery.loginError = true;
            });
        }
    
		this.load = function( page ) {
        
			if ( this.token !== null && localStorage.getItem('page') != null ) {
				this.page = localStorage.getItem('page');
			}
		
			if ( page !== undefined )
				this.page = page;
		
			var url = this.api+"?page&nb="+this.page;		
			this.loading = true;
            
            if ( this.token !== null )
                url += '&token='+this.token;
        
			// filters
			if ( this.filterAdult != 0 )
				url += '&adult=' + this.filterAdult;
		
			$http({
			  method: 'GET',
			  url: url
			}).then(function successCallback(response) {
				
				if ( gallery.page > response.data.pages ) {
					gallery.page = response.data.pages;
					gallery.load(gallery.page);
				}
				
				//if ( gallery.token != null ){
					localStorage.setItem('page', gallery.page);
				//}
				
				gallery.loading = false;	
                gallery.files = response.data.files;
                gallery.error = false;
                gallery.pages = response.data.pages;
				$("html, body").animate({ scrollTop: 0 }, "slow");
			  }, function errorCallback(response) {
				gallery.loading = false;
                gallery.error = true;
			  });
		
		},
        
        app.directive('imageonload', function() {
            return {
                restrict: 'A',
                link: function(scope, element, attrs) {
                    element.bind('load', function() {
                        //call the function that was passed
                        scope.$apply(attrs.imageonload);
                    });
                }
            };            
        });
	
	}]);

})();