#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 72 - Перевозка фурами

Fact -> "фура" | "фурами" | "еврофура";

S -> Fact interp(Category.Category_72);
