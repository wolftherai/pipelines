# Pipelines

## Run Bitbucket Pipelines Wherever They Dock

[![Build Status](https://travis-ci.org/ktomk/pipelines.svg?branch=master)](https://travis-ci.org/ktomk/pipelines)

Command line pipeline runner written in PHP, easy to install with
Composer.

## Usage

From anywhere within a project or (Git*) repository with a
Bitbucket Pipeline file:

~~~
pipelines
~~~

Runs pipeline commands from [`bitbucket-pipelines.yml`][BBPL]
\[BBPL\].

Memory and time limits are ignored. Press ctrl+c to quit.

The Bitbucket limit of 10 steps per pipeline is ignored.

Exit status is from last pipeline script command, if a command
fails the following script commands and steps are not executed.

The default pipeline is run, if there is no default pipeline in
the file, pipelines tells it and exists with non-zero status.

To execute a different pipeline use the `--pipeline <id>` option
where `<id>` is one of the list by the `--list` option. Even more
information about the pipelines is available via `--show`. Both
`--list` and `--show` will output and exit.

Run the pipeline as if a tag/branch or bookmark has been pushed
with `--trigger <ref>` where `<ref>` is `tag:<name>`,
`branch:<name>` or `bookmark:<name>`. If there is no tag, branch
or bookmark pipeline with that name, the name is compared against
the patterns of the referenced pipelines type and if found, that
pipeline is run. Otherwise the default pipeline is run, if there
is no default pipeline, no pipeline at all is run and the command
exits with non-zero status.

`--pipeline` and `--trigger` can be used together, `--pipeline`
overrides pipeline from `--trigger` but `--trigger` still
influences the container environment variables.

TODO: get revision from working directory (VCS)

To specify a different file use the  `--basename <basename>`
or `--file <path>` option and/or set the working directory
`--working-dir <path>` in which the file is looked for unless
an absolute path is set by `--file <path>`.

TOOD Use a different project directory `--project-dir <path>` for
the working tree to operate on which otherwise is the working
directory.

By default it operates on the current working tree, TODO(VCS): to
run on a specific revision, reference it (`--revision <ref>`). TODO: The
pipeline is then run against a clean temporary checkout of
the current (Git*) repository or TODO: a copy of the working directory
(`--vcs "none"`; `--working-dir <directory>`; `--project-dir`).

Isolate files by copying them into the container instead of the
mount by using `--deploy copy`. This requires `docker copy` with
the `-a` / `--archive` option (e.g. docker client 17.12.0-ce).

Use `--keep` flag to keep containers after the pipeline has
finished for further inspection. By default all containers are
destroyed on successful pipeline step execution.

Manage leftover containers with `--docker-list` showing all
pipeline containers, `--docker-kill` to kill running containers
and `--docker-clean` to remove stopped pipeline containers. Use
in combination to fully clean, e.g.:

    $ pipelines --docker-list --docker-kill --docker-clean

Validate your bitbucket-pipelines.yml file with `--show` which
highlights errors found.

Inspect your pipeline with `--dry-run` which will process the
pipeline but not execute anything. Combine with `--verbose` to
show the commands which would have run verbatim.

Use `--no-run` to not run the pipeline at all.

----

\* Mercurial support: No yet; TODO(VCS): Git is planned to be
  used as first VCS test case, after VCS interaction is more
  clear and/or demand is high, others (e.g. Mercurial) should be
  supported. VCS integration is yet on the TODO list. However
  all references can be used to `--trigger` a specific pipeline
  including Mercurial bookmarks.

### Usage Scenario

Give your project and pipeline changes a quick test run from the
staging area. As pipelines are normally executed far away,
setting them up becomes cumbersome, especially with the guide
given in [Bitbucket Pipelines documentation][BBPL-LOCAL-RUN]
\[BBPL-LOCAL-RUN\].

This is where the `pipelines` command jumps in.

The `pipelines` command closes the gap between local development
and remote pipeline execution. As long as Docker is installed
and running, the `bitbucket-pipelines.yml` file is parsed and
it is taken care of to execute all steps and their commands
within the container of choice.

Pipelines YAML file parsing, container creation and script
execution is done as closely as possible compared to the
Atlassian Bitbucket Pipeline service. Features include:

* **Dev Mode**: Pipeline from your working tree like never
  before. Pretend to be on any branch, tag or bookmark
  (`--trigger`) even in a different repository or none at all.

  Check if the reference matches a pipeline or just run the
  default (default) or a specific one (`--pipeline`). Use a
  different pipelines file (`--file`) or swap the "repository" by
  changing the working directory (`--working-dir`).

  If a pipeline step fails, the steps container will be
  automatically kept for further inspection. The container id is
  shown which makes it easy to spawn a shell inside:

      $ docker exec -it $ID /bin/sh

  Containers can be always kept for debugging and manual testing
  of a pipeline with `--keep`.

  Afterwards manage left overs with `--docker-list|kill|clean`.

  Debugging options to dream for.

* **Container Isolation**: There is one container per step, like
  it is on Bitbucket.

  To isolate the files, use `--deploy` with `copy` to copy the
  files into the container. This isolates file-changes done by
  the pipeline from the working directory which is otherwise by
  default mounted into the container (implicit `--deploy mount`).

* **Ready for Offline**: On the plane? Or just a rainy day on
  a remote location with broken net? Coding while abroad? Or
  just Bitbucket down again? Before going into offline mode, make
  use of `--images` and the shell:

      $ pipelines --images | while read -r image; do \
        docker image pull "$image"; done;

* **Default Image**: The pipelines command uses the default
  image like Bitbucket Pipelines does. Get started out of the
  box, but keep in mind it has roughly 2 GB. TODO: The default
  image can even be overridden (`--default-image`).

* **Pipelines inside Pipeline**: As a special feature and by
  default pipelines mounts the docker socket into each container
  (on systems where the socket is available). That allows to
  launch pipelines from a pipeline as long as the docker client
  is available in the pipeline's container.
  This feature is similar to [run Docker commands in Bitbucket
  Pipelines][BBPL-DCK] \[BBPL-DCK\] but w/o mounting the Docker
  CLI executable in the container (TODO: provide on option).
  The pipelines inside pipeline feature serves pipelines itself
  well for integration testing on Travis. In combination with
  `--deploy mount` (the default), the original working-directory
  gets mounted from the host again. Additional protection against
  recursion is implemented to prevent accidental endless loops
  of pipelines inside pipeline invocations.

## Environment

Pipelines mimics all of the [Bitbucket Pipeline in-container
environment variables][BBPL-ENV] \[BBPL-ENV\]:


* `BITBUCKET_BOOKMARK` - conditionally set by `--target`
* `BITBUCKET_BUILD_NUMBER` - always set to '0'
* `BITBUCKET_BRANCH` - conditionally set by `--target`
* `BITBUCKET_CLONE_DIR` - always set to mount point in container
* `BITBUCKET_COMMIT` - faux as no revision triggers a build;
    always set to '0000000000000000000000000000000000000000'
* `BITBUCKET_REPO_OWNER` - current username from
    environment or if not available 'nobody'
* `BITBUCKET_REPO_SLUG` - always set to 'local-has-no-slug'
* `BITBUCKET_TAG` - conditionally set by `--target`
* `CI` - always set to 'true'

All of these (but not `BITBUCKET_CLONE_DIR`) can be set within
the environment pipelines runs and are taken over into container
environment. Example:

    $ BITBUCKET_BUILD_NUMBER=1234 pipelines # build no. 1234

Additionally pipelines sets some environment variables for
introspection:

* `PIPELINES_CONTAINER_NAME` - name of the container itself
* `PIPELINES_ID` - <id> of the pipeline that currently runs
* `PIPELINES_IDS` - list of space separated md5 hashes of so
    far running <id>. used to detect pipelines inside pipelines
    recursion, preventing execution until system failure.
* `PIPELINES_PARENT_CONTAINER_NAME` - name of the container name
    if it was already set when the pipeline started (pipelines
    inside pipeline).

These environment variables are managed by pipelines itself and
can not be injected.

## Details

### Requirements

Pipelines requires a POSIX compatible system supporting the User
Portability Utilities option.

Docker needs to be available locally as `docker` command as it is
used to run pipelines and the working directory is used as volume
in the container (by default as a mount unless `--deploy` with
`copy` instead of the implicit `mount`). (1)

A recent PHP version is favored, the `pipelines` command needs
it to run. It should work with PHP 5.3+, the phar build requires
PHP 5.4+. A development environment should have PHP 7, this is
especially suggested for future releases.

(1) There is the idea to allow bare-metal execution of pipeline
commands, but this is more specific and can run against pipeline
assumptions (there is no container environment) so is more of
a pipelines file development option (TODO).

### User Tests

Successful use on Ubuntu 16.04 LTS and Mac OS X Sierra and High
Sierra with PHP and Docker installed.

### Known Bugs

- Version number shows only the revision hash (`--version`) for
  phar builds and a placeholder for the development version;
  should be gone with a tagged release for phar files (TODO:
  version for development version).
- The command ':' in pipelines exec layer is never really exec'ed
  and just has exit status 0 and no standard or error output. It
  is intended for pipelines testing.

### Installation

Installation is available via Composer. Suggested is to install
it globally (and to have the global composer bin in PATH) so that
there are no dependencies in a local project:

    $ composer global require ktomk/pipelines

This will automatically install the latest available version.
Verify the installation by invoking pipelines and output the
version:

    $ pipelines --version
    pipelines version 0.0.1

To uninstall remove the package:

    $ composer global remove ktomk/pipelines

Alternatively checkout the source repository and symlink the
executable bin/pipelines into a segment of PATH, e.g. your
HOME/bin directory or similar. Verify the installation by
invoking pipelines and output the version:

    $ pipelines --version
    pipelines version 0.0.1

To create a phar archive from sources, invoke from within the
projects root directory the build script:

    $ composer build
    > @php -dphar.readonly=0 -fdist/build/build.php # build phar file
    building 0.0.1 ...
    pipelines version 0.0.1
    file.....: build/pipelines.phar
    size.....: 155 498 bytes
    SHA-1....: 69aa4996d5fc27840f0fe5d2ef04586b8d88171c
    SHA-256..: 48d37a278aff1f71bef12b2c338662460b68a17991f1c6573a2b16b073d4dfea
    count....: 30 file(s)
    signature: SHA-1 B9B1D9DCC8C6AAC4D736E52CE866C03A373972A4

The phar archive then is (as written in the output of the build):

    build/pipelines.phar

Check the version by invoking it:

    $ build/pipelines.phar --version
    pipelines version 0.0.1


### Todo

- Phar build as Github releases
- Check Docker existence before running
- Pass environment variable file(s) (`--env-file`)
- Stop at manual steps (`--no-manual` to override)
- Run specific steps of a pipeline only
- More accessible offline preparation (`--docker-pull-images`)
- Write limitations section about the file-format support
- Pipeline file properties support
    - clone (1)
    - max-time (1)
    - size (1)
    - step.trigger (1)
    - artifacts (1)
    - definitions (1)
- Automatically keep container if step failed, use `--no-fail-keep` to prevent
- Validation command for bitbucket-pipelines.yml files (so far
  `--show` gives error on parts it runs over and non zero exit
  code)
- Verify steps of a single command to see if it matches an
  executable script file or not in the project.
- Write exit status section about used exit codes

(1) if it is considered that it applies to running local

## References

* \[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
* \[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
* \[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html
* \[BBPL-DCK\]: https://confluence.atlassian.com/bitbucket/run-docker-commands-in-bitbucket-pipelines-879254331.html

[BBPL]: https://confluence.atlassian.com/bitbucket/configure-bitbucket-pipelines-yml-792298910.html
[BBPL-ENV]: https://confluence.atlassian.com/bitbucket/environment-variables-794502608.html
[BBPL-LOCAL-RUN]: https://confluence.atlassian.com/bitbucket/debug-your-pipelines-locally-with-docker-838273569.html
[BBPL-DCK]: https://confluence.atlassian.com/bitbucket/run-docker-commands-in-bitbucket-pipelines-879254331.html
