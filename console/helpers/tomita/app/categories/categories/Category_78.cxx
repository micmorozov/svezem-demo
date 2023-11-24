#encoding "utf-8"
#GRAMMAR_ROOT S

// Категория 78 - Перевозка длинномером

Fact -> "длинномер" | "длинный" | "длинномерный";

S -> Fact interp(Category.Category_78);
