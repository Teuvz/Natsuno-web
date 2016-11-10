(function() {
	
	var app = angular.module('gallery', []);
	
    
	app.controller('GalleryController', ['$http','$location',function($http,$location) {
	
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
		
		this.filterAdult = false;
	
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
                gallery.downloadVisible = false;
				gallery.downloading = false;
				gallery.downloadUrl = '';
			});
        }
		
        this.selectContent = function( id ) {
            $('#'+id).select();
            console.log('select '+id+' content');
        }
        
        this.rename = function() {
            
            this.renaming = true;
            
            $http({
				method: 'GET',
				url: this.api + '?rename&token=' + this.token
			}).then(function successCallback(response) {
                gallery.load(gallery.page);
				gallery.renaming = false;
			});
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
				url: this.api + '?adult&id='+file.name+'&value='+!file.adult
			}).then(function successCallback(response){
				gallery.loading = false;
				//this.load(this.page);
				gallery.selected.adult = !gallery.selected.adult;
			},function errorCallback(response){
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
			if ( this.filterAdult == true ) {
				url += '&adult=true';
			}
		
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