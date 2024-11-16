const gulp = require('gulp');
const browserSync = require('browser-sync').create();

gulp.task('serve', () => {
    browserSync.init({
        proxy: "http://localhost:2088",
        port: 3000,
        reloadOnRestart: true,
        cache: false,
        notify: true,
        open: true
    });

    gulp.watch(["**/*.php"]).on("change", (path) => {
      console.log(`File changed: ${path}`);
      browserSync.reload();
  });
  
});

gulp.task('default', gulp.series('serve'));
