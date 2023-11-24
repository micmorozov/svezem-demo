#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 85 - Автомобильная перевозка

Fact -> "частная" | "частник";

S -> Fact interp(Category.Category_86);
