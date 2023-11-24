#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 9 - Перевозка сыпучих грузов

What -> "песок" | "щебень" | "сыпучий";

Fact -> What;

S -> Fact interp(Category.Category_9);