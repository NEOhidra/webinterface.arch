angular.module('app')
.controller('PopularShowsCtrl', ['$scope', '$filter',
  function PopularShowsCtrl($scope, $filter) {
    $scope.loading = true;
    $scope.fetching = false;
    $scope.tvshows = [];
    $scope.pages = 1;
    $scope.total = Infinity;
    $scope.supportIndex = -1;

    var now = new Date();
    var firstAirDate = (now.getFullYear()-5)+'-01-01';
    var cleanUpResults = function(results) {
      return results.filter(function(show){
        return show.rating > 0;
      });
    };

    function onTvShowsFromSource(response) {
      $scope.total = response.data.totalPages;
      $scope.tvshows = $scope.tvshows.concat(cleanUpResults(response.data.results));
      if($scope.supportIndex === -1) {
        $scope.supportIndex = 5 + Math.floor(Math.random()*($scope.tvshows.length-5));
      }
      $scope.fetching = false;
      $scope.loading = false;
    };

    $scope.tmdb.tv.populars(firstAirDate, 5, $scope.pages).then(onTvShowsFromSource);

    $scope.hasControls = function () {
      return false;
    };

    $scope.getEpisodesPath = function(show) {
      return '#/tvshows/tmdb/'+show.id;
    };

    $scope.getExtra = function (show) {
      return moment(show.firstaired).format('YYYY');
    };

    $scope.getPoster = function (show) {
      var url = $filter('tmdbImage')(show.poster, 'w500');
      return $filter('fallback')(url, 'img/icons/awe-512.png');
    };
;

    $scope.getStudio = function(show) {
      return 'img/icons/default-studio.png';
    };

    $scope.loadMore = function () {
      if( $scope.pages < $scope.total) {
        $scope.fetching = true;
        $scope.tmdb.tv.populars(firstAirDate, 5, ++$scope.pages).then(onTvShowsFromSource);
      }
    };

  }]
);