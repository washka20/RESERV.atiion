/**
 * Conventional Commits enforcement.
 * Allowed types match .claude/rules/git.md — префикс маленькой буквой, глагол в прошедшем.
 */
module.exports = {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      ['feat', 'fix', 'refactor', 'docs', 'test', 'chore', 'style', 'build', 'ci', 'perf', 'feature', 'merge'],
    ],
    'subject-case': [0],
    'subject-max-length': [2, 'always', 120],
  },
}
