@servers(['web' => '-p 231 root@120.24.48.2'])

@task('deploy')
    cd /www/web/hanbao/public_html
    git pull origin master
@endtask
