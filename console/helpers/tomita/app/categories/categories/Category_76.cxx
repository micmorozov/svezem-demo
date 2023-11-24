#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 76 - Наливная перевозка

Fact -> "цистерна" | "наливная" | "автоцистерна";

S -> Fact interp(Category.Category_76);
